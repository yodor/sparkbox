<?php
include_once("utils/IGETConsumer.php");
include_once("objects/SparkObject.php");

class OrderColumn extends SparkObject
{
    const string ASC = "ASC";
    const string DESC = "DESC";

    protected string $label = "";
    protected string $direction = OrderColumn::DESC;

    static function Empty() : OrderColumn
    {
        return new OrderColumn("", "", "");
    }

    public function __construct(string $name, string $label, string $order_direction = OrderColumn::DESC)
    {
        parent::__construct();
        $this->name = $name;
        $this->label = $label;
        $this->direction = $order_direction;
    }

    /**
     * Label for this sort column
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Initial ordering direction
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): void
    {
        $this->direction = $direction;
    }

    public function toString() : string
    {
        return $this->name." ".$this->direction;
    }

}

class Paginator implements IGETConsumer
{

    protected int $ipp = 10;
    protected int $total_rows = 0;

    protected int $page = 0;
    protected float $total_pages = 0;

    protected int $page_list_end = 0;
    protected int $page_list_start = 0;

    /**
     * @var int Number of page selection items
     */
    protected int $page_list_items = 5;

    /**
     * @var bool Show prev/next arrow buttons for page selection
     */
    protected bool $show_next = true;
    protected bool $show_prev = true;

    /**
     * Defined sort fields
     * @var array
     */
    protected array $order_columns = array();


    /**
     * Current ordering
     * @var OrderColumn|null
     */
    protected ?OrderColumn $activeOrder = null;

    const string KEY_ORDER_BY = "orderby";
    const string KEY_ORDER_DIR = "orderdir";
    const string KEY_PAGE = "page";
    const string KEY_VIEW = "view";

    static protected ?Paginator $instance = NULL;

    /**
     * Return last instance of Paginator class if no instance
     * has been created return new empty/non-calculated
     * instance of Paginator class
     * @return Paginator
     */
    static public function Instance() : Paginator
    {
        if (is_null(self::$instance)) {
            self::$instance = new Paginator();
        }

        return self::$instance;
    }

    public function getParameterNames(): array
    {
        return array(self::KEY_ORDER_BY, self::KEY_ORDER_DIR, self::KEY_PAGE);
    }

    public function __construct()
    {

        $this->activeOrder = OrderColumn::Empty();

        if (isset($_GET[Paginator::KEY_ORDER_DIR])) {
            $this->activeOrder->setDirection(DBConnections::Open()->escape(sanitizeInput($_GET[Paginator::KEY_ORDER_DIR])));
        }

        if (isset($_GET[Paginator::KEY_ORDER_BY])) {
            if (!$this->activeOrder->getDirection()) {
                $this->activeOrder->setDirection(OrderColumn::DESC);
            }
            $this->activeOrder->setName(DBConnections::Open()->escape(sanitizeInput($_GET[Paginator::KEY_ORDER_BY])));
        }


        if (isset($_GET[Paginator::KEY_PAGE])) {
            $this->page = (int)$_GET[Paginator::KEY_PAGE];
        }

        self::$instance = $this;
    }

    public function getActiveOrdering(): ?OrderColumn
    {
        return $this->activeOrder;
    }

    public function addOrderColumn(OrderColumn $sort_field): void
    {
        $this->order_columns[$sort_field->getName()] = $sort_field;
    }

    public function getOrderColumns() : array
    {
        return $this->order_columns;
    }

    /**
     * Return the selected ordercolumn referenced from _GET or the first ordercolumn if no reference is found.
     * Returns null if no PaginatorOrderColumns are added
     * @return OrderColumn|null
     */
    public function getSelectedOrderColumn() : ?OrderColumn
    {
        if (count($this->order_columns) == 0) return null;

        $result = null;

        //check if the query parameter matches order column name
        foreach ($this->order_columns as $column => $sort_field) {
            if (strcmp_isset(Paginator::KEY_ORDER_BY, $column, $_GET)) {
                $result = $sort_field;
                break;
            }
        }

        //return the first order column - ie default order
        if (is_null($result)) {
            foreach ($this->order_columns as $element) {
                $result = $element;
                break;
            }
        }

        return $result;
    }

    public function itemsPerPage() : int
    {
        return $this->ipp;
    }

    public function pageListStart() : int
    {
        return $this->page_list_start;
    }

    public function pageListEnd() : int
    {
        return $this->page_list_end;
    }

    public function hasNextPage() : bool
    {
        return $this->show_next;
    }

    public function hasPrevPage() : bool
    {
        return $this->show_prev;
    }

    public function totalPages() : int
    {
        if ($this->total_pages == (int)$this->total_pages) return $this->total_pages;
        return ((int)$this->total_pages) + 1;
    }

    public function resultsTotal() : int
    {
        return $this->total_rows;
    }

    public function currentPage() : int
    {
        return $this->page;
    }

    public function calculate(int $total_rows, int $ipp): void
    {
        $this->ipp = $ipp;
        $this->total_rows = $total_rows;

        if ($this->ipp<1) {
            $this->page = 0;
            $this->page_list_start = 1;
            $this->page_list_end = 1;
            $this->total_pages = 1;
            $this->show_prev = false;
            $this->show_next = false;
            return;
        }

        $this->total_pages = (float)$this->total_rows / (float)$this->ipp;

        if ($this->total_pages != (int)$this->total_pages) {
            $this->total_pages = (int)$this->total_pages + 1;
        }

        if ($this->page > $this->total_pages) $this->page = $this->total_pages - 1;
        if ($this->page < 0) $this->page = 0;

        $this->page_list_start = $this->page - (int)($this->page_list_items / 2);
        $this->page_list_end = $this->page + (int)($this->page_list_items / 2) + 1;

        if ($this->page_list_start < 2) {
            $this->page_list_start = 0;
            $this->page_list_end = $this->page_list_items;
        }
        if ($this->page_list_end > $this->total_pages) {
            $this->page_list_end = $this->total_pages;
        }

        $this->show_next = FALSE;
        if ($this->page_list_end < $this->total_pages) {
            $this->show_next = TRUE;
        }

        $this->show_prev = FALSE;
        if ($this->page > 0) {
            $this->show_prev = TRUE;

        }

    }

    public function getOrderingSelect(string $default_order = "") : SQLSelect
    {

        $filter = new SQLSelect();
        $filter->from = "";
        $filter->order_by = $default_order;

        $selectedOrderColumn = $this->getSelectedOrderColumn();

        if (!$selectedOrderColumn) {
            //direct ordering passed in _GET
            if ($this->activeOrder->getName()) {
                if (!$this->activeOrder->getDirection()) {
                    $this->activeOrder->setDirection(OrderColumn::DESC);
                }
                $filter->order_by = $this->activeOrder->toString();
            }
            return $filter;
        }

        //using PaginatorOrderColumn items
        if (!$this->activeOrder->getDirection()) {
            $this->activeOrder->setDirection($selectedOrderColumn->getDirection());
        }
        $this->activeOrder->setName($selectedOrderColumn->getName());

        $filter->order_by = $this->activeOrder->toString();

        return $filter;
    }

    public function getLimitingSelect() : SQLSelect
    {

        $filter = new SQLSelect();
        $filter->from = "";

        if ($this->ipp > 0) {
            $filter->limit = " " . ($this->page * $this->ipp) . ", $this->ipp ";
        }

        return $filter;
    }
}
