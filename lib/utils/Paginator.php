<?php
include_once("utils/IGETConsumer.php");
include_once("objects/SparkObject.php");
include_once("sql/OrderColumn.php");
include_once("utils/InputSanitizer.php");
include_once("components/InlinePageScript.php");

class UpdateListInlineScript extends InlinePageScript implements IPageComponent
{

    public function __construct()
    {
        parent::__construct(false);
    }

    public function code(): string
    {
        $keyOrderBy = Paginator::KEY_ORDER_BY;
        $keyOrderDir = Paginator::KEY_ORDER_DIR;
        $keyPage = Paginator::KEY_PAGE;

        return <<<JS
function updateList(elm) {

    let url = new URL(window.location);

    let clearKeys = ['$keyOrderDir','$keyPage'];
    if (clearKeys instanceof Array) {
        clearKeys.forEach(key => url.searchParams.delete(key));
    }    
    
    //require order by - get from the select
    if (!url.searchParams.has("$keyOrderBy")) {
        //use the first value of the select element
        const orderByValue = elm.parentNode.querySelector("[name='$keyOrderBy']").value;
        url.searchParams.set("$keyOrderBy", orderByValue);
    }
    
    const key = elm.getAttribute("key");
    let value = "";
    if (elm instanceof HTMLSelectElement) {
        value = elm.value;
    }
    else {
        value = elm.getAttribute("action");
    }
    url.searchParams.set(key, value);
    // Redirect to updated URL (preserves path, hash, and other parts)
    window.location = url;
}
JS;

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
    protected bool $show_next = false;
    protected bool $show_prev = false;

    /**
     * Defined sort fields
     * @var array<string, OrderColumn>
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
        if (is_null(Paginator::$instance)) {
            Paginator::$instance = new Paginator();
        }

        return Paginator::$instance;
    }

    public function getParameterNames(): array
    {
        return array(self::KEY_ORDER_BY, self::KEY_ORDER_DIR, self::KEY_PAGE);
    }

    public function __construct()
    {

        new UpdateListInlineScript();

        $this->activeOrder = null;

        //process GET variables and setup activeOrder
        if (isset($_GET[Paginator::KEY_ORDER_BY])) {

            $columnName = $_GET[Paginator::KEY_ORDER_BY];
            if (InputSanitizer::SafeSQLColumn($columnName)) {
                $this->activeOrder = new OrderColumn($columnName);
                $this->activeOrder->setDirection(OrderDirection::DESC);

                if (isset($_GET[Paginator::KEY_ORDER_DIR])) {
                    $direction = OrderDirection::tryFrom($_GET[Paginator::KEY_ORDER_DIR]);
                    if (!is_null($direction)) {
                        $this->activeOrder->setDirection($direction);
                    }
                }
            }
        }

        if (isset($_GET[Paginator::KEY_PAGE])) {
            $this->page = (int)$_GET[Paginator::KEY_PAGE];
        }

        Paginator::$instance = $this;
    }

    public function getActiveOrder(): ?OrderColumn
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
        if ($this->page < $this->total_pages-1) {
            $this->show_next = TRUE;
        }

        $this->show_prev = FALSE;
        if ($this->page > 0) {
            $this->show_prev = TRUE;

        }

    }

    public function applyOrder(SQLSelect $select, ?OrderColumn $defaultOrder = null) : void
    {

        if ($this->activeOrder) {
            $select->orderColumn($this->activeOrder);
        }
        else {
            if (count($this->order_columns) > 0) {
                foreach ($this->order_columns as $columnName=>$orderColumn) {
                    $select->orderColumn($orderColumn);
                    break;
                }
            }
            else {
                if ($defaultOrder) {
                    $select->orderColumn($defaultOrder);
                }
            }
        }


    }

    public function applyLimit(SQLSelect $select) : void
    {
        //if stmt is already having a limit - pagination will not work
        if ($select->isLimited()) {
            throw new Exception("Pagination LIMIT is requested but Iterator already has LIMIT set");
        }
        $select->limit($this->ipp, ($this->page * $this->ipp));
    }
}