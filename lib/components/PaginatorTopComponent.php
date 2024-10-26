<?php
include_once("components/PaginatorComponent.php");

class PaginatorTopComponent extends PaginatorComponent
{
    public $view_modes_enabled = FALSE;

    public function __construct(Paginator $paginator)
    {
        parent::__construct($paginator);
        $this->addClassName("PaginatorTopComponent");
    }

    public function renderImpl()
    {
        if ($this->view_modes_enabled) {
            echo "<div class='cell view_mode'>";

                $link = URL::Current();
                $link->remove(Paginator::KEY_PAGE);

                $link->add(new URLParameter(Paginator::KEY_VIEW, "list"));

                $listURL = $link->toString();
                echo "<a class='icon list' href='$listURL'></a>";

                $link->get(Paginator::KEY_VIEW)->setValue("grid");
                $gridURL = $link->toString();
                echo "<a class='icon grid' href='$gridURL'></a>";

            echo "</div>";
        }

        if ($this->caption) {
            echo "<div class='cell caption'>" . tr($this->caption) . "</div>";
        }

        $this->renderSortFields();

        echo "<div class='cell page_navigation short' nowrap>";

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

        echo "<span class='nav_buttons'>";
        $this->drawPrevButton();
        $this->drawNextButton();
        echo "</span>";

        echo "</div>";

    }
}

?>
