<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");

include_once("utils/Paginator.php");
include_once("components/ResultViewHeader.php");
include_once("components/ResultViewFooter.php");

abstract class AbstractResultView extends Container implements IDataIteratorRenderer
{
    const int PAGINATOR_NONE = 0;
    const int PAGINATOR_TOP = 1;
    const int PAGINATOR_BOTTOM = 2;

    protected int $items_per_page = 20;

    /**
     * SQL order by clause
     * @var OrderColumn|null
     */
    protected ?OrderColumn $default_order = null;

    protected bool $results_paginated = false;
    /**
     *
     * @var int Total result rows for this data iterator
     */
    protected int $total_rows = 0;

    /**
     * @var int total number of results for the current iteration
     */
    protected int $paged_rows = 0;

    /**
     * @var Paginator|null
     */
    protected ?Paginator $paginator = NULL;

    /**
     * @var ResultViewHeader|null
     */
    protected ?ResultViewHeader $header = NULL;

    /**
     * @var ResultViewFooter|null
     */
    protected ?ResultViewFooter $footer = NULL;


    protected int $position_index = -1;

    /**
     * @var DataIteratorItem|null
     */
    protected ?DataIteratorItem $item_renderer = null;

    /**
     * @var Component|null
     */
    protected ?Component $list_empty = null;

    /**
     * @var SelectQuery|IDataIterator|null
     */
    protected ?SelectQuery $iterator = null;

    protected Container $viewport;

    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct(false);

        $this->iterator = $itr;

        $this->paginator = new Paginator();

        $this->header = new ResultViewHeader($this->paginator);

        $this->footer = new ResultViewFooter($this->paginator);

        $this->list_empty = new Component(false);
        $this->list_empty->addClassName("ListEmpty");

        $this->viewport = new ClosureComponent($this->renderItems(...));
        $this->viewport->setComponentClass("viewport");


        $this->items()->append($this->header);
        $this->items()->append($this->list_empty);
        $this->items()->append($this->viewport);
        $this->items()->append($this->footer);

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/ResultView.css";
        return $arr;
    }

    /**
     * Rendered if list results are zero ie only shown for empty lists
     * @return Component
     */
    public function getListEmpty() : Component
    {
        return $this->list_empty;
    }

    public function setListEmpty(Component $cmp) : void
    {
        $this->list_empty = $cmp;
    }

    public function setListEmptyMessage(string $message): void
    {
        $this->list_empty->setContents($message);
    }

    public function getListEmptyMessage(): string
    {
        return $this->list_empty->getContents();
    }

    /**
     * Enable paginated results if `$item_count is > 0.
     *
     * Sets the number of items per page paginated.
     *
     * @param int $item_count
     * @return void
     */
    public function setItemsPerPage(int $item_count) : void
    {
        $this->items_per_page = $item_count;
    }

    public function getItemsPerPage() : int
    {
        return $this->items_per_page;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    /**
     * @throws Exception
     */
    public function setIterator(IDataIterator $itr): void
    {
        if (!($itr instanceof SelectQuery)) throw new Exception("Unsuitable iterator. Expecting SelectQuery");
        $this->iterator = $itr;
        $this->results_paginated = false;
    }

    public function setItemRenderer(DataIteratorItem $renderer): void
    {
        $this->item_renderer = $renderer;
        $this->item_renderer->setParent($this);
    }

    public function getItemRenderer(): DataIteratorItem
    {
        return $this->item_renderer;
    }

    public function getTotalRows(): int
    {
        return $this->total_rows;
    }

    public function getPositionIndex(): int
    {
        return ($this->paginator->currentPage() * $this->paginator->itemsPerPage()) + $this->position_index;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function getHeader(): ResultViewHeader
    {
        return $this->header;
    }

    public function getFooter(): ResultViewFooter
    {
        return $this->footer;
    }

    public function setDefaultOrder(OrderColumn $default_order) : void
    {
        $this->default_order = $default_order;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCacheName() : string
    {
        $result = parent::getCacheName();
        if ($this->iterator instanceof ICacheIdentifier) {

            $result.= "-".$this->iterator->getCacheName();
        }
        return $result;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->setAttribute("pagesTotal", $this->paginator->totalPages());
        $this->setAttribute("page", ($this->paginator->currentPage()+1));
    }

    /**
     * @throws Exception
     */
    public function startRender(): void
    {
        if (is_null($this->iterator)) {
            $this->list_empty->setRenderEnabled(true);
            $this->list_empty->setContents("No iterator set");
            $this->viewport->setRenderEnabled(false);

        }
        else {
            $this->processIterator();
            if ($this->total_rows > 0) {
                $this->list_empty->setRenderEnabled(false);
            }
            else {
                $this->list_empty->setRenderEnabled(true);
                $this->viewport->setRenderEnabled(false);
                $this->header->setRenderEnabled(false);
                $this->footer->setRenderEnabled(false);
            }
        }

        parent::startRender();
    }

    public function paginate() : void
    {
        if ($this->results_paginated) return;

        //unbuffered mode
        //get the result count only
        $this->total_rows = $this->iterator->count();

        $this->paginator->applyOrder($this->iterator->stmt, $this->default_order);

        //edge case - set default for footer visible without pagination
        $this->paginator->calculate($this->total_rows, $this->items_per_page);

        if ($this->items_per_page>0) {
            $this->paginator->applyLimit($this->iterator->stmt);
        }

        $this->iterator->stmt->setMeta("ARV Paginated");
        $this->results_paginated = true;
    }
    /**
     * Execute the assigned SelectQuery and prepare the paginator values.
     * Need to call this once
     * @return void
     * @throws Exception
     */
    public function processIterator() : void
    {
        if (!($this->iterator instanceof SelectQuery)) {
            Debug::ErrorLog("No iterator set");
            return;
        }
        if ($this->iterator->isActive()) {
            Debug::ErrorLog("Already active");
            return;
        }

        //might be already be paginated by page head calling AppendHeadLinks
        $this->paginate();

        //echo "Final SQL: ".$this->iterator->select->getSQL();
        //exec will clear cached count for all results so count will return only paginated

        $this->paged_rows = $this->iterator->count();
        //exec after count
        $this->iterator->exec();
    }

    protected function renderItems() : void
    {
        $this->position_index = 0;
        while ($result = $this->iterator->nextResult())
        {
            $this->renderItem($result);
            $this->position_index++;
        }
    }

    abstract protected function renderItem(RawResult $result) : void;

    public static function AppendHeadLinks(AbstractResultView $view, SparkPage $sparkPage) : void
    {
        //execute pagination query - only count results no fetching from db server
        $view->paginate();

        $paginator = $view->getPaginator();
        $currentPage = $paginator->currentPage();
        if ($paginator->hasPrevPage()) {
            $page = $currentPage - 1;
            $url = $sparkPage->currentURL();
            $link = new Link();
            $link->setRelation("prev");
            $url->add(new URLParameter("page", $page));
            if ($page < 1) {
                $url->remove("page");
            }
            $link->setHref($url->fullURL());
            $sparkPage->head()->items()->append($link);
        }
        if ($paginator->hasNextPage()) {
            $page = $currentPage + 1;
            $url = $sparkPage->currentURL();
            $link = new Link();
            $link->setRelation("next");
            $url->add(new URLParameter("page", $page));
            $link->setHref($url->fullURL());
            $sparkPage->head()->items()->append($link);
        }

        if ($paginator->totalPages() > 1) {
            //apply page number to the title
            $headTitle = $sparkPage->head()->getTitle();
            $sparkPage->head()->setTitle($headTitle . " | " . tr("Page") . " " . ($currentPage + 1));
        }
    }
}