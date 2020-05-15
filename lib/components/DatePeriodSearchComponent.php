<?php
include_once("components/Component.php");
include_once("input/renderers/DateField.php");
include_once("components/InputRowComponent.php");
include_once("utils/SQLSelect.php");

class DatePeriodSearchComponent extends Component
{

    protected $pstart = NULL;
    protected $pend = NULL;

    public $formadd = "";

    public function __construct()
    {
        parent::__construct();

        if (isset($_GET["clear_filter"])) {

            unset($_GET["period_start_day"]);
            unset($_GET["period_start_month"]);
            unset($_GET["period_start_year"]);
            unset($_GET["period_start"]);
            unset($_GET["period_end_day"]);
            unset($_GET["period_end_month"]);
            unset($_GET["period_end_year"]);
            unset($_GET["period_end"]);
            unset($_GET["clear_filter"]);

            $qry = queryString($_GET);
            header("Location: " . $_SERVER["PHP_SELF"] . "$qry");

            exit;

        }

        $this->pstart = new DataInput("period_start", "Period Start", 0);
        $this->pstart->setRenderer(new DateField());
        $this->pstart->processPost($_GET);
        $this->pstart->validate();

        $this->pend = new DataInput("period_end", "Period End", 0);
        $this->pend->setRenderer(new DateField());
        $this->pend->processPost($_GET);
        $this->pend->validate();

    }

    public function startRender()
    {
        echo "<form method=get >";
        echo "<table>";
    }

    public function finishRender()
    {
        echo "</table>";
        echo $this->formadd;
        echo "</form>";
    }

    public function getPeriodStartField()
    {
        return $this->pstart;
    }

    public function getPeriodEndField()
    {
        return $this->pend;
    }

    public function renderImpl()
    {

        $ir = new InputRowComponent($this->pstart, InputRowComponent::VERTICAL);
        $ir->render();

        $ir = new InputRowComponent($this->pend, InputRowComponent::VERTICAL);
        $ir->render();

        echo "<tr><td>";
        ColorButton::RenderSubmit("Clear Filter", "clear_filter");
        ColorButton::RenderSubmit("Filter Dates", "filter_dates");
        echo "</td></tr>";

    }

    public function processSelectQuery(SQLSelect $sqry, $date_field_name = "item_date")
    {
        $psd = $this->pstart->getValue();
        $ped = $this->pend->getValue();

        if (strlen($psd) > 0) {

            $psq = new SQLSelect();
            $psq->fields = "";
            $psq->where = " $date_field_name >= timestamp('$psd 00:00:00') ";
            $sqry = $sqry->combineWith($psq);

        }
        if (strlen($ped) > 0) {
            $peq = new SQLSelect();
            $peq->fields = "";
            $peq->where = " $date_field_name <= timestamp('$ped 23:59:59') ";
            $sqry = $sqry->combineWith($peq);
        }
        return $sqry;
    }

}