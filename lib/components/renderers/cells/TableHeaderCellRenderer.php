<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class TableHeaderCellRenderer extends TableCellRenderer
{

    protected $tagName = "TH";
    protected $sort_field = "";
    protected $sort_href = "";

    public function __construct($is_sortable = TRUE)
    {
        parent::__construct();

    }

    public function isSortable()
    {
        return $this->column->isSortable();
    }

    public function setSortable(bool $mode)
    {
        $this->column->setSortable($mode);
    }

    public function setSortField(string $field)
    {
        $this->sort_field = $field;
    }

    protected function renderImpl()
    {
        if ($this->column->isSortable()) {

            echo "<a href='$this->sort_href'>" . $this->value . "</a>";
            $arr = "&darr;";
            if (isset($_GET["orderdir"]) && strcmp($_GET["orderdir"], "DESC") == 0) {
                $arr = "&uarr;";
            }
            if (isset($_GET["orderby"]) && strcmp($_GET["orderby"], $this->sort_field) == 0) {
                echo "<span class='sort_direction'>$arr</span>";
            }
        }
        else {
            parent::renderImpl();
        }
    }

    public function setData(array &$row)
    {

        parent::setData($row);

        $this->value = $this->column->getLabel();

        if ($this->isSortable()) {

            $qry = $_GET;

            if (!$this->sort_field) {
                $this->sort_field = $this->column->getFieldName();
            }

            $odir = "ASC";

            if (isset($qry["orderdir"]) && (isset($qry["orderby"]) && strcmp($qry["orderby"], $this->sort_field) == 0)) {
                if (strcmp($qry["orderdir"], "ASC") == 0) {
                    $odir = "DESC";
                }
                else {
                    $odir = "ASC";
                }
            }

            $qry["orderby"] = $this->sort_field;
            $qry["orderdir"] = $odir;

            $this->sort_href = queryString($qry);

        }

    }

}

?>