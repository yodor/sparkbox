<?php
include_once("components/PaginatorComponent.php");

class PaginatorTopComponent extends PaginatorComponent
{
    public $view_modes_enabled = FALSE;

    public function renderImpl()
    {
        if ($this->view_modes_enabled) {
            echo "<div class='cell view_mode'>";

            $qry = $_GET;
            if (isset($qry["page"])) unset($qry["page"]);

            $qry["view"] = "list";
            $qrystr = queryString($qry);
            echo "<a class='icon list' href='$qrystr'></a>";

            $qry["view"] = "grid";
            $qrystr = queryString($qry);
            echo "<a class='icon grid' href='$qrystr'></a>";

            echo "</div>";
        }

        if ($this->caption) {
            echo "<div class='cell caption'>" . tr($this->caption) . "</div>";
        }

        $this->renderSortFields();

        $this->renderSortComponents();

        echo "<div class='cell page_navigation short' nowrap>";

        echo "<label>" . tr("Page") . "</label>";

        $page = 0;
        if ($this->paginator->getPagesTotal() > 0) {
            $page = $this->paginator->getCurrentPage() + 1;
        }
        echo $page;
        echo "&nbsp;";
        echo " / ";
        echo "&nbsp;";
        echo $this->paginator->getPagesTotal();

        echo "<span class='nav_buttons'>";
        $this->drawPrevButton();
        $this->drawNextButton();
        echo "</span>";

        echo "</div>";

    }
}

?>
