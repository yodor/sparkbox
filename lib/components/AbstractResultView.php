<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");

include_once("utils/Paginator.php");
include_once("components/PaginatorTopComponent.php");
include_once("components/PaginatorBottomComponent.php");

abstract class AbstractResultView extends Component implements IDataIteratorRenderer
{

    protected $items_per_page = 20;

    /**
     * SQL order by clause
     * @var string
     */
    protected $default_order = "";

    /**
     * Total results row after executing the query
     * @var int
     */
    protected $total_rows = 0;

    protected $current_row = array();

    /**
     * @var Paginator|null
     */
    protected $paginator = NULL;

    /**
     * @var PaginatorTopComponent|null
     */
    protected $paginator_top = NULL;

    /**
     * @var PaginatorBottomComponent|null
     */
    protected $paginator_bottom = NULL;


    protected $position_index = -1;

    protected $paginators_enabled = TRUE;

    /**
     *  @var DataIteratorItem
     */
    protected $item_renderer = null;

    /**
     * @var SQLQuery
     */
    protected $iterator;

    const PAGINATOR_NONE = 0;
    const PAGINATOR_TOP = 1;
    const PAGINATOR_BOTTOM = 2;

    public function __construct(?IDataIterator $itr=null)
    {
        parent::__construct();

        $this->iterator = $itr;

        $this->paginator = new Paginator();
        $this->paginator_top = new PaginatorTopComponent($this->paginator);
        $this->paginator_bottom = new PaginatorBottomComponent($this->paginator);
        $this->paginators_enabled = AbstractResultView::PAGINATOR_TOP | AbstractResultView::PAGINATOR_BOTTOM;
    }

    public function setItemsPerPage(int $item_count) {
        $this->items_per_page = $item_count;
    }

    public function getItemsPerPage() : int {
        return $this->items_per_page;
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

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

        $position_index = ($paginator->getCurrentPage() * $paginator->getItemsPerPage()) + $this->position_index;

        return $position_index;
    }

    public function enablePaginators(int $mode)
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

    public function setDefaultOrder($default_order)
    {
        $this->default_order = $default_order;
    }

    /**
     * @throws Exception
     */
    public function startRender()
    {

        parent::startRender();

        if (is_null($this->iterator)) {
            echo "No iterator set";
            return;
        }

        $select = clone $this->iterator->select;
        $select->setMode(SQLSelect::SQL_CALC_FOUND_ROWS);
        $select->setMode(SQLSelect::SQL_CACHE);
        $select->limit = 1;

        $db = $this->iterator->getDB();
        if (!$db->query($select->getSQL())) {
            debug("Error selecting all number of records: " . $db->getError() . " SQL: " . $select->getSQL());
            throw new Exception($db->getError());
        }
        $res = $db->query("SELECT FOUND_ROWS() as total");
        $result = $db->fetch($res);
        $this->total_rows = (int)$result["total"];
        $db->free($res);

        $this->paginator->calculate($this->total_rows, $this->items_per_page);


        $orderFilter = $this->paginator->prepareOrderFilter($this->default_order);

        $pageFilter = $this->paginator->preparePageFilter();

        if ($this->paginators_enabled) {
            $this->iterator->select->combine($pageFilter);
            $this->iterator->select->combine($orderFilter);
        }

        $this->iterator->select->setMode(SQLSelect::SQL_CACHE);
        //echo "Final SQL: ".$this->iterator->select->getSQL();

        //number of results for the current page - limited by $this->items_per_page
        $pageResults = $this->iterator->exec();

        if($this->paginators_enabled & AbstractResultView::PAGINATOR_TOP) {

            $this->paginator_top->render();

        }
    }

    /**
     * @throws Exception
     */
    public function finishRender()
    {
        if($this->paginators_enabled & AbstractResultView::PAGINATOR_BOTTOM) {
            $this->paginator_bottom->render();
        }

        parent::finishRender();

    }

}

?>