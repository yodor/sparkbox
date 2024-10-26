<?php
include_once("components/Container.php");
include_once("utils/IGETConsumer.php");

abstract class PaginatorComponent extends Container implements IGETConsumer
{
    protected Paginator $paginator;

    public function __construct(Paginator $paginator)
    {
        parent::__construct(false);
        $this->setComponentClass("PaginatorComponent");
        $this->paginator = $paginator;

    }

    /**
     * @return array The parameter names this object is interacting with
     */
    public function getParameterNames(): array
    {
        return Paginator::Instance()->getParameterNames();
    }


    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/PaginatorComponent.css";
        return $arr;
    }

    public function setPaginator(Paginator $paginator) : void
    {
        $this->paginator = $paginator;
    }

    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    public function drawPrevButton() : void
    {
        $link = URL::Current();
        $link->add(new URLParameter(Paginator::KEY_PAGE));

        if ($this->paginator->currentPage() > 0) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() - 1));
            echo "<a class='previous_page' href='{$link->toString()}'> < " . tr("Prev") . " </a>";
        }
    }

    public function drawNextButton() : void
    {
        $link = URL::Current();
        $link->add(new URLParameter(Paginator::KEY_PAGE));

        if (($this->paginator->currentPage() + 1) < $this->paginator->totalPages()) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() + 1));
            echo "<a  class='next_page' href='{$link->toString()}'>" . tr("Next") . " > </a>";
        }
    }

    protected function renderSortFields() : void
    {
        $items = $this->paginator->getOrderColumns();

        if (count($items) < 1) return;

        echo "<div class='cell sort_fields' nowrap>";

            echo "<label>";
            echo tr("Sort By");
            echo "</label>";

            echo "<select name='".Paginator::KEY_ORDER_BY."' onChange='javascript:changeSort(this)'>";

            $link = URL::Current();
            $link->setClearParams(Paginator::KEY_PAGE);
            $link->add(new URLParameter(Paginator::KEY_ORDER_BY, ""));
            $link->add(new URLParameter(Paginator::KEY_ORDER_DIR, ""));

            $selectedField = $this->paginator->getSelectedOrderColumn();

            foreach ($items as $column => $sortField) {
                if (!($sortField instanceof OrderColumn)) continue;
                $selected = "";

                if ($selectedField && strcmp($sortField->getName(), $selectedField->getName()) == 0) {
                    $selected = " SELECTED ";
                }

                $link->get(Paginator::KEY_ORDER_BY)->setValue($sortField->getName());
                $link->get(Paginator::KEY_ORDER_DIR)->setValue($sortField->getDirection());

                echo "<option $selected value='{$link->toString()}' >" . tr($sortField->getLabel()) . "</option>";

            }
            echo "</select>";

            $activeOrdering = $this->paginator->getActiveOrdering();

           // $active_direction = $this->paginator->getOrderDirection();

            //next click toggle direction
            $direction = OrderColumn::ASC;
            if (strcmp($activeOrdering->getDirection(), OrderColumn::ASC) == 0) {
                $direction = OrderColumn::DESC;
            }
            $link->get(Paginator::KEY_ORDER_DIR)->setValue($direction);

            echo "<a class='direction' direction='$direction' href='{$link->toString()}'></a>";

        echo "</div>";

        ?>
        <script type='text/javascript'>
            function changeSort(sel) {
                window.location.href = sel.options[sel.options.selectedIndex].value;
            }
        </script>
        <?php
    }

    protected function renderPageSelector() : void
    {

        $link = URL::Current();
        $link->add(new URLParameter(Paginator::KEY_PAGE));

        echo "<div class='pager'>";

        $a = $this->paginator->pageListStart();

//        if ($this->paginator->havePreviousPage() || $this->paginator->haveNextPage()) {
//            echo "<label>" . tr("Page") . "</label>";
//        }

        if ($this->paginator->currentPage() > 0) {

            $link->get(Paginator::KEY_PAGE)->setValue(0);
            echo "<a  href='{$link->toString()}' title='".tr("First")."'> <<  </a>";

            $link->get(Paginator::KEY_PAGE)->setValue($this->paginator->currentPage() - 1);
            echo "<a   href='{$link->toString()}' title='".tr("Prev")."'> <  </a>";

        }

        while ($a < $this->paginator->pageListEnd()) {

            $link->get(Paginator::KEY_PAGE)->setValue($a);

            $link_class = "";
            if ($this->paginator->currentPage() == $a) {
                $link_class = "class=selected";
            }

            echo "<a $link_class  href='{$link->toString()}'>" . ($a + 1) . "</a>";
            $a++;
        }

        if (($this->paginator->currentPage() + 1) < $this->paginator->totalPages()) {

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() + 1));
            echo "<a  href='{$link->toString()}' title='".tr("Next")."'> > </a>";

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->totalPages() - 1));
            echo "<a  href='{$link->toString()}' title='".tr("Last")."'> >> </a>";
        }

        echo "</div>";
    }
}

?>
