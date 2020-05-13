<?php
include_once("components/Component.php");

include_once("utils/Paginator.php");
include_once("components/PaginatorTopComponent.php");
include_once("components/PaginatorBottomComponent.php");

abstract class AbstractResultView extends Component
{

    public $items_per_page = 10;

    protected $itr = NULL;
    protected $default_order = "";
    protected $total_rows = 0;
    protected $current_row = array();
    protected $paginator = NULL;
    protected $paginator_top = NULL;
    protected $paginator_bottom = NULL;
    protected $position_index = -1;
    protected $paginators_enabled = true;
    protected $select_query = NULL;

    public function __construct(IDataIterator $itr)
    {
        parent::__construct();

        $this->itr = $itr;
        $this->columns = array();
        $this->paginator = new Paginator();
        $this->paginator_top = new PaginatorTopComponent($this->paginator);
        $this->paginator_bottom = new PaginatorBottomComponent($this->paginator);
    }

    public function getIterator() : IDataIterator
    {
        return $this->itr;
    }

    public function getTotalRows() : int
    {
        return $this->total_rows;
    }

    public function getPositionIndex() : int
    {
        $paginator = $this->paginator;

        $position_index = ($paginator->getCurrentPage() * $paginator->getItemsPerPage()) + $this->position_index;

        return $position_index;
    }

    public function enablePaginators($mode)
    {
        $this->paginators_enabled = $mode;
    }

    public function getPaginator() : Paginator
    {
        return $this->paginator;
    }

    public function getTopPaginator() : PaginatorTopComponent
    {
        return $this->paginator_top;
    }

    public function getBottomPaginator() : PaginatorBottomComponent
    {
        return $this->paginator_bottom;
    }

    public function setCaption(string $caption)
    {
        //parent::setCaption($caption);
        $this->paginator_top->setCaption($caption);
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

        $this->total_rows = $this->itr->exec();

        $this->paginator->calculate($this->total_rows, $this->items_per_page);

        $pageFilter = $this->paginator->preparePageFilter($this->default_order);

        //	echo "PageFilter SQL: ".$pageFilter->getSQL(true);
        //	echo "Iterator SQL: ".$select->getSQL();

        if ($this->paginators_enabled) {
            $this->itr->select->combine($pageFilter);
        }

        //echo "Final SQL: ".$select->getSQL();

        $this->total_rows = $this->itr->exec();

        if ($this->paginators_enabled) {
            $this->paginator_top->render();
        }
    }


    /**
     * @throws Exception
     */
    public function finishRender()
    {
        if ($this->paginators_enabled) {
            $this->paginator_bottom->render();
        }

        parent::finishRender();
    }


}

?>