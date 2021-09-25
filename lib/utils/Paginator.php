<?php
include_once("utils/IGETConsumer.php");

class PaginatorSortField
{

    public $value;
    public $label;
    public $extended_sort_sql;
    public $order_direction;

    public function __construct($value, $label, $extended_sort_sql = "", $order_direction = "DESC")
    {

        $this->value = $value;
        $this->label = $label;
        $this->extended_sort_sql = $extended_sort_sql;
        $this->order_direction = $order_direction;
    }
}

class Paginator implements IGETConsumer
{

    protected $ipp = 10;
    protected $total_rows = 0;

    protected $show_next = TRUE;
    protected $show_prev = TRUE;

    protected $total_pages = 0;
    protected $page = 0;

    protected $page_list_end = 0;
    protected $page_list_start = 0;

    public $max_page_list = 5;

    public static $page_filter_only = FALSE;

    /**
     * Container for PaginatorSortField
     * @var array
     */
    protected $sort_fields = array();

    /**
     * SQL order by direction ASC|DESC
     * @var null
     */
    protected $order_direction = "";

    /**
     * SQL order field name
     * @var null
     */
    protected $order_field = "";

    const KEY_ORDER_BY = "orderby";
    const KEY_ORDER_DIR = "orderdir";
    const KEY_PAGE = "page";

    static protected $instance = NULL;

    public function __construct()
    {
        self::$instance = $this;
    }

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

    public function getOrderDirection()
    {
        return $this->order_direction;
    }

    public function getOrderField()
    {
        return $this->order_field;
    }

    public function addSortField(PaginatorSortField $sort_field)
    {
        $this->sort_fields[$sort_field->value] = $sort_field;
    }

    public function getSortFields() : array
    {
        return $this->sort_fields;
    }

    public function getSelectedSortField() : ?PaginatorSortField
    {
        if (count($this->sort_fields) == 0) return null;

        $result = null;

        //check if the query parameter matches the sort field
        foreach ($this->sort_fields as $field_value => $sort_field) {
            if (strcmp_isset(Paginator::KEY_ORDER_BY, $sort_field->value, $_GET)) {
                $result = $sort_field;
            }
        }

        //return the first sort field
        if (is_null($result)) {
            $values = array_values($this->sort_fields);
            $result = array_shift($values);
        }

        return $result;
    }

    public function getItemsPerPage() : int
    {
        return $this->ipp;
    }

    public function getPageListStart() : int
    {
        return $this->page_list_start;
    }

    public function getPageListEnd() : int
    {
        return $this->page_list_end;
    }

    public function haveNextPage() : bool
    {
        return $this->show_next;
    }

    public function havePreviousPage() : bool
    {
        return $this->show_prev;
    }

    public function getPagesTotal() : int
    {
        if ($this->total_pages == (int)$this->total_pages) return $this->total_pages;
        return ((int)$this->total_pages) + 1;
    }

    public function getResultsTotal() : int
    {
        return $this->total_rows;
    }

    public function getCurrentPage() : int
    {
        return $this->page;
    }

    public function calculate(int $total_rows, int $ipp)
    {
        $this->ipp = $ipp;
        $this->total_rows = $total_rows;

        if ($ipp > 0) {
            $total_pages = (float)$this->total_rows / (float)$this->ipp;

            if ($total_pages != (int)$total_pages) {
                $total_pages = (int)$total_pages + 1;
            }

            $qry = $_GET;

            $page = 0;
            if (isset($_GET[Paginator::KEY_PAGE])) {
                $page = (int)$_GET[Paginator::KEY_PAGE];
            }

            if ($page > $total_pages) $page = $total_pages - 1;
            if ($page < 0) $page = 0;

            echo " ";

            $max_page = $this->max_page_list;

            $cstart = $page - (int)($max_page / 2);
            $cend = $page + (int)($max_page / 2) + 1;

            if ($cstart < 2) {
                $cstart = 0;
                $cend = $max_page;
            }
            if ($cend > $total_pages) {
                $cend = $total_pages;
            }
            $this->show_next = FALSE;
            if ($cend < $total_pages) {
                $this->show_next = TRUE;
            }
            $this->show_prev = FALSE;
            if ($cstart > 0) {
                $this->show_prev = TRUE;

            }
        }
        else {
            $page = 0;
            $cend = 1;
            $cstart = 1;
            $total_pages = 1;
            $this->show_prev = FALSE;
            $this->show_next = FALSE;
        }
        $this->page = (int)$page;
        $this->page_list_end = (int)$cend;
        $this->page_list_start = (int)$cstart;
        $this->total_pages = (int)$total_pages;
    }

    public static function clearPageFilter(array &$arr)
    {
        if (isset($arr[Paginator::KEY_PAGE])) unset($arr[Paginator::KEY_PAGE]);
        if (isset($arr[Paginator::KEY_ORDER_DIR])) unset($arr[Paginator::KEY_ORDER_DIR]);
        if (isset($arr[Paginator::KEY_ORDER_BY])) unset($arr[Paginator::KEY_ORDER_BY]);
    }

    public function prepareOrderFilter(string $default_order = "") : SQLSelect
    {

        $filter = new SQLSelect();
        $filter->from = "";
        $filter->order_by = $default_order;

        $sort_field = $this->getSelectedSortField();

        if ($sort_field) {

            $this->order_direction = $sort_field->order_direction;

            if ($sort_field->extended_sort_sql) {
                $this->order_field = $sort_field->extended_sort_sql;
            }
            else {
                $this->order_field = $sort_field->value;
            }
        }
        else {

            if (isset($_GET[Paginator::KEY_ORDER_BY])) {
                $this->order_field = DBConnections::Get()->escape($_GET[Paginator::KEY_ORDER_BY]);
            }

        }

        if (isset($_GET[Paginator::KEY_ORDER_DIR])) {
            $this->order_direction = DBConnections::Get()->escape($_GET[Paginator::KEY_ORDER_DIR]);
        }

        if (!self::$page_filter_only) {
            if ($this->order_field && $this->order_direction) {
                $filter->order_by = $this->order_field. " " .$this->order_direction;
            }
        }

        return $filter;
    }

    public function preparePageFilter(int $items_per_page) : SQLSelect
    {
        $this->ipp = $items_per_page;

        $filter = new SQLSelect();
        $filter->from = "";

        $page = 0;
        if (isset($_GET[Paginator::KEY_PAGE])) {
            $page = (int)$_GET[Paginator::KEY_PAGE];
        }

        if ($page < 0) $page = 0;

        $this->page = $page;

        if ($this->ipp > 0) {
            $filter->limit = " " . ($this->page * $this->ipp) . ", {$this->ipp}";
        }

        return $filter;
    }
}