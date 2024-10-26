<?php
include_once("components/PaginatorComponent.php");

class PaginatorBottomComponent extends PaginatorComponent
{
    public function __construct(Paginator $paginator)
    {
        parent::__construct($paginator);
        $this->addClassName("PaginatorBottomComponent");
    }

    public function renderImpl()
    {

        echo "<div class='cell page_info'>";

        echo "<label>" . tr("Page") . "</label>";

        $page = 0;
        if ($this->paginator->totalPages() > 0) {
            $page = $this->paginator->currentPage() + 1;
        }
        echo $page;
        echo "&nbsp;";
        echo " / ";
        echo "&nbsp;";
        echo $this->paginator->totalPages();

        echo "</div>";

        echo "<div class='cell page_navigation long'>";
        if ($this->paginator->totalPages() > 1) {
            $this->renderPageSelector();
        }
        echo "</div>";

        echo "<div class='cell results_info'>";
        echo "<label>" . tr("Results") . "</label>";
        echo "<span>" . $this->paginator->resultsTotal() . "</span>";
        echo "</div>";

    }
}

?>
