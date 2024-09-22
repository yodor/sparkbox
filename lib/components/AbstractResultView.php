<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");

include_once("utils/Paginator.php");
include_once("components/PaginatorTopComponent.php");
include_once("components/PaginatorBottomComponent.php");

abstract class AbstractResultView extends Component implements IDataIteratorRenderer
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
     * Total results row after executing the query
     * @var int
     */
    protected int $total_rows = 0;

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
     *  @var DataIteratorItem
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



    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct(false);

        $this->iterator = $itr;

        $this->paginator = new Paginator();
        $this->paginator_top = new PaginatorTopComponent($this->paginator);
        $this->paginator_bottom = new PaginatorBottomComponent($this->paginator);
        $this->paginators_enabled = (AbstractResultView::PAGINATOR_TOP | AbstractResultView::PAGINATOR_BOTTOM);

        $this->list_empty = new Component(false);
        $this->list_empty->addClassName("ListEmpty");
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
    public function setIterator(IDataIterator $itr)
    {
        if (!($itr instanceof SQLQuery)) throw new Exception("Unsuitable iterator. Expecting SQLQuery");
        $this->iterator = $itr;
    }

    public function setItemRenderer(DataIteratorItem $renderer)
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
        $paginator = $this->paginator;

        return ($paginator->getCurrentPage() * $paginator->getItemsPerPage()) + $this->position_index;

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

        $orderFilter = $this->paginator->prepareOrderFilter($this->default_order);
        $pageFilter = $this->paginator->preparePageFilter();

        $select->combine($pageFilter);
        $select->combine($orderFilter);

        return parent::getCacheName()."-".$select->getSQL();
    }

    /**
     * @throws Exception
     */
    public function startRender()
    {
        if (is_null($this->iterator)) {
            echo "No iterator set";
            return;
        }

        $this->processIterator();

        $this->setAttribute("pagesTotal", $this->paginator->getPagesTotal());
        $this->setAttribute("page", $this->paginator->getCurrentPage());

        parent::startRender();

        if(($this->paginators_enabled & AbstractResultView::PAGINATOR_TOP) && $this->total_rows>0) {
            $this->paginator_top->render();
        }

        if ($this->total_rows<1) {
            $this->list_empty->render();
        }
    }

    /**
     * @throws Exception
     */
    public function finishRender()
    {
        if(($this->paginators_enabled & AbstractResultView::PAGINATOR_BOTTOM) && $this->total_rows>0) {
            $this->paginator_bottom->render();
        }

        parent::finishRender();

    }

    /**
     * @return void
     * @throws Exception
     */
    public function processIterator() : void
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

        $orderFilter = $this->paginator->prepareOrderFilter($this->default_order);
        $pageFilter = $this->paginator->preparePageFilter();

        $this->iterator->select->combine($pageFilter);
        $this->iterator->select->combine($orderFilter);

        $this->iterator->select->setMode(SQLSelect::SQL_NO_CACHE);

        //echo "Final SQL: ".$this->iterator->select->getSQL();

        $this->paged_rows = $this->iterator->exec();
    }
}

?>
