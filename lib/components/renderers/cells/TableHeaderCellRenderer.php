<?php
include_once("components/Component.php");
include_once("components/renderers/ICellRenderer.php");
include_once("components/TableColumn.php");

class TableHeaderCellRenderer extends Component implements ICellRenderer
{
    protected $is_sortable = false;

    protected $tooltip_field = "";
    protected $tooltip_text = "";

    protected $sort_field = "";

    public function __construct($is_sortable = true)
    {
        parent::__construct();
        $this->is_sortable = $is_sortable;
    }

    public function startRender()
    {
        $all_attribs = $this->prepareAttributes();
        echo "<th $all_attribs>";
    }

    public function finishRender()
    {
        echo "</th>";
    }

    protected function processAttributes($row, TableColumn $tc)
    {
        $this->setAttribute("column", $tc->getFieldName());
    }

    public function isSortable()
    {
        return ($this->is_sortable > 0);
    }

    public function setSortable($mode)
    {
        $this->is_sortable = ($mode > 0);
    }

    public function setSortField($sort_field)
    {
        $this->sort_field = $sort_field;
    }

    protected function renderImpl()
    {

    }

    public function renderCell(array &$row, TableColumn $tc)
    {

        $this->processAttributes($row, $tc);

        $this->startRender();

        if ($this->isSortable()) {
            $qry = $_GET;

            $odir = "ASC";

            $sort_field = $tc->getFieldName();
            if ($this->sort_field) {
                $sort_field = $this->sort_field;
            }
            if (isset($qry["orderdir"]) && (isset($qry["orderby"]) && strcmp($qry["orderby"], $sort_field) == 0)) {
                if (strcmp($qry["orderdir"], "ASC") == 0) {
                    $odir = "DESC";
                }
                else {
                    $odir = "ASC";
                }
            }

            $qry["orderby"] = $sort_field;
            $qry["orderdir"] = $odir;
            $qrystr = queryString($qry);

            echo "<a href='$qrystr'>" . tr($tc->getLabel()) . "</a>";
            $arr = "&darr;";
            if (isset($_GET["orderdir"]) && strcmp($_GET["orderdir"], "DESC") == 0) {
                $arr = "&uarr;";
            }
            if (isset($_GET["orderby"]) && strcmp($_GET["orderby"], $sort_field) == 0) {
                echo "<span style='font-weight:bold;margin-left:5px;font-size:14px;'>$arr</span>";
            }
        }
        else {
            echo "<span>" . tr($tc->getLabel()) . "</span>";
        }

        $this->finishRender();
    }

    public function setTooltipFromField($field_name)
    {
        $this->tooltip_field = $field_name;
    }
}

?>