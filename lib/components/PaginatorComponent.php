<?php
include_once("components/Component.php");

abstract class PaginatorComponent extends Component
{
    protected $paginator = FALSE;

    public function __construct(Paginator $paginator)
    {

        parent::__construct();

        $this->paginator = $paginator;

        //$this->component_class = "PaginatorComponent";

    }

    public function renderCaption()
    {
        //parent::renderCaption();
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/PaginatorComponent.css";
        return $arr;
    }

    public function setPaginator(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function drawPrevButton()
    {
        $qry = $_GET;

        if ($this->paginator->getCurrentPage() > 0) {
            $qry["page"] = $this->paginator->getCurrentPage() - 1;
            $q = queryString($qry);
            echo "<a class='previous_page' href='$q'> << " . tr("Prev") . " </a>";
        }
    }

    public function drawNextButton()
    {
        $qry = $_GET;

        if (($this->paginator->getCurrentPage() + 1) < $this->paginator->getPagesTotal()) {
            $qry["page"] = ($this->paginator->getCurrentPage() + 1);
            $q = queryString($qry);
            echo "<a  class='next_page' href='$q'>" . tr("Next") . " >> </a>";
        }
    }

    protected function renderSortComponents()
    {
        $sort_components = $this->paginator->getSortComponents();

        if (count($sort_components) < 1) return;

        echo "<div class='cell sort_components' nowrap>";
        foreach ($sort_components as $idx => $cmp) {
            $cmp->render();
        }
        echo "</div>";
    }

    protected function renderSortFields()
    {
        $sort_fields = $this->paginator->getSortFields();

        if (count($sort_fields) < 1) return;

        echo "<div class='cell sort_fields' nowrap>";

        echo "<label>";
        echo tr("Sort By");
        echo "</label>";

        echo "<select name=orderby onChange='javascript:changeSort(this)'>";

        foreach ($sort_fields as $field_name => $sort_field) {

            $selected = "";

            if (strcmp($sort_field->value, $this->paginator->getOrderField()) == 0) {
                $selected = " SELECTED ";
            }

            $dir_qry = $_GET;
            $dir_qry["orderby"] = $sort_field->value;
            $dir_qry["orderdir"] = $this->paginator->default_order_direction;

            $dir_href = queryString($dir_qry);

            echo "<option $selected value='$dir_href' >" . tr($sort_field->label) . "</option>";

        }
        echo "</select>";

        $order_field = $this->paginator->getOrderField();
        $order_direction = $this->paginator->getOrderDirection();

        $dir_qry = $_GET;
        $dir_qry["orderby"] = $this->paginator->getOrderField();
        $dir_label = "&#11014;";
        $direction = "ASC";
        if (strcmp($order_direction, "ASC") == 0) {
            $dir_label = "&#11015;";
            $direction = "DESC";
        }
        $dir_qry["orderdir"] = $direction;
        $dir_href = queryString($dir_qry);

        echo "<a class='direction' href='$dir_href'>$dir_label</a>";

        echo "</div>";

        ?>
        <script language=javascript>
            function changeSort(sel) {
                var href = sel.options[sel.options.selectedIndex].value;
// alert(href);
                window.location.href = href;
            }
        </script>
        <?php
    }

    protected function renderPageSelector()
    {
        $qry = $_GET;

        $a = $this->paginator->getPageListStart();

        if ($this->paginator->havePreviousPage() || $this->paginator->haveNextPage()) {
            echo "<label>" . tr("Page") . "</label>";
        }

        if ($this->paginator->getCurrentPage() > 0) {

            $qry["page"] = 0;
            $q = queryString($qry);
            echo "<a  href='$q'> < " . tr("First") . " </a>";

            $qry["page"] = $this->paginator->getCurrentPage() - 1;
            $q = queryString($qry);
            echo "<a   href='$q'> << " . tr("Prev") . " </a>";

        }

        while ($a < $this->paginator->getPageListEnd()) {
            $qry["page"] = $a;

            $q = queryString($qry);

            $link_class = "";
            if ($this->paginator->getCurrentPage() == $a) {
                $link_class = "class=selected";

            }

            echo "<a $link_class  href='$q'>" . ($a + 1) . "</a>";
            $a++;
        }

        if (($this->paginator->getCurrentPage() + 1) < $this->paginator->getPagesTotal()) {
            // 		if ($this->paginator->haveNextPage())
            // 		{
            $qry["page"] = ($this->paginator->getCurrentPage() + 1);
            $q = queryString($qry);
            echo "<a  href='$q'>" . tr("Next") . " >> </a>";

            $qry["page"] = (int)($this->paginator->getPagesTotal() - 1);
            $q = queryString($qry);
            echo "<a  href='$q'>" . tr("Last") . " > </a>";
        }
    }
}

?>
