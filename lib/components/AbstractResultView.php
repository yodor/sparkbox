<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");

include_once("utils/Paginator.php");
include_once("components/PaginatorTopComponent.php");
include_once("components/PaginatorBottomComponent.php");

abstract class AbstractResultView extends Container implements IDataIteratorRenderer
{
    const int PAGINATOR_NONE = 0;
    const int PAGINATOR_TOP = 1;
    const int PAGINATOR_BOTTOM = 2;

    protected int $items_per_page = 20;

    /**
     * SQL order by clause
     * @var string
     */
    protected string $default_order = "";

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
     * @var PaginatorTopComponent|null
     */
    protected ?PaginatorTopComponent $paginator_top = NULL;

    /**
     * @var PaginatorBottomComponent|null
     */
    protected ?PaginatorBottomComponent $paginator_bottom = NULL;


    protected int $position_index = -1;

    protected int $paginators_enabled = AbstractResultView::PAGINATOR_NONE;


    /**
     * @var DataIteratorItem|null
     */
    protected ?DataIteratorItem $item_renderer = null;

    /**
     * @var Component|null
     */
    protected ?Component $list_empty = null;


    /**
     * @var SQLQuery|IDataIterator|null
     */
    protected ?SQLQuery $iterator;

    protected Container $viewport;

    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct(false);


        $this->iterator = $itr;

        $this->paginator = new Paginator();

        $this->paginator_top = new PaginatorTopComponent($this->paginator);
        $this->paginator_top->setRenderEnabled(false);

        $this->paginator_bottom = new PaginatorBottomComponent($this->paginator);
        $this->paginator_bottom->setRenderEnabled(false);

        $this->paginators_enabled = (AbstractResultView::PAGINATOR_TOP | AbstractResultView::PAGINATOR_BOTTOM);

        $this->list_empty = new Component(false);
        $this->list_empty->addClassName("ListEmpty");

        $this->viewport = new ClosureComponent($this->renderItems(...));
        $this->viewport->setComponentClass("viewport");


        $this->items()->append($this->paginator_top);
        $this->items()->append($this->list_empty);
        $this->items()->append($this->viewport);
        $this->items()->append($this->paginator_bottom);

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
     * Max number of results per page
     * set to -1 to disable paged results
     * @param int $item_count
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
        if (!($itr instanceof SQLQuery)) throw new Exception("Unsuitable iterator. Expecting SQLQuery");
        $this->iterator = $itr;
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

    public function enablePaginators(int $mode) : void
    {
        $this->paginators_enabled = $mode;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function getTopPaginator(): PaginatorTopComponent
    {
        return $this->paginator_top;
    }

    public function getBottomPaginator(): PaginatorBottomComponent
    {
        return $this->paginator_bottom;
    }

    public function setDefaultOrder($default_order) : void
    {
        $this->default_order = $default_order;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCacheName() : string
    {
        if (!($this->iterator instanceof SQLQuery)) return "";

        $select = clone $this->iterator->select;

        $orderFilter = $this->paginator->getOrderingSelect($this->default_order);
        $pageFilter = $this->paginator->getLimitingSelect();

        $select->combine($pageFilter);
        $select->combine($orderFilter);

        return parent::getCacheName()."-".$select->getSQL();
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->setAttribute("pagesTotal", $this->paginator->totalPages());
        $this->setAttribute("page", $this->paginator->currentPage());


    }

    /**
     * @throws Exception
     */
    public function startRender()
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

                if ($this->paginators_enabled & AbstractResultView::PAGINATOR_TOP) {
                    $this->paginator_top->setRenderEnabled(true);
                }
                if($this->paginators_enabled & AbstractResultView::PAGINATOR_BOTTOM) {
                    $this->paginator_bottom->setRenderEnabled(true);
                }
            }
            else {
                $this->viewport->setRenderEnabled(false);
            }

        }

        parent::startRender();

    }


    /**
     * @return void
     * @throws Exception
     */
    protected function processIterator() : void
    {
        if ($this->iterator->isActive()) {
            debug("Already active");
            return;
        }

        $select = clone $this->iterator->select;
        $select->setMode(SQLSelect::SQL_CALC_FOUND_ROWS);
        $select->setMode(SQLSelect::SQL_CACHE);

        //do not reset the fields here as 'custom' columns might be used with grouping or having clauses
        //ie select (select field from table1) as custom_name from table2 having custom_name LIKE '%something%'
        $select->limit = "";

        //echo "Count SQL: ".$select->getSQL();
        $db = $this->iterator->getDB();
        $result = $db->query($select->getSQL());
        if (! ($result instanceof DBResult) ) {
            debug("Error fetching SQL_CALC_FOUND_ROWS: " . $select->getSQL());
            throw new Exception("Unable to query SQL_CALC_FOUND_ROWS");
        }

        $this->total_rows = $result->numRows();

        $result->free();

        $this->paginator->calculate($this->total_rows, $this->items_per_page);

        $orderFilter = $this->paginator->getOrderingSelect($this->default_order);
        $pageFilter = $this->paginator->getLimitingSelect();

        $this->iterator->select->combine($pageFilter);
        $this->iterator->select->combine($orderFilter);

        $this->iterator->select->setMode(SQLSelect::SQL_NO_CACHE);

       //echo "Final SQL: ".$this->iterator->select->getSQL();

        $this->paged_rows = $this->iterator->exec();
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

}

?>
