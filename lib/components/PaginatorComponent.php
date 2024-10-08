<?php
include_once("components/Component.php");
include_once("utils/IGETConsumer.php");

abstract class PaginatorComponent extends Component implements IGETConsumer
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

        if ($this->paginator->getCurrentPage() > 0) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->getCurrentPage() - 1));
            echo "<a class='previous_page' href='{$link->toString()}'> < " . tr("Prev") . " </a>";
        }
    }

    public function drawNextButton() : void
    {
        $link = URL::Current();
        $link->add(new URLParameter(Paginator::KEY_PAGE));

        if (($this->paginator->getCurrentPage() + 1) < $this->paginator->getPagesTotal()) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->getCurrentPage() + 1));
            echo "<a  class='next_page' href='{$link->toString()}'>" . tr("Next") . " > </a>";
        }
    }

    protected function renderSortFields() : void
    {
        $sort_fields = $this->paginator->getSortFields();

        if (count($sort_fields) < 1) return;

        echo "<div class='cell sort_fields' nowrap>";

            echo "<label>";
            echo tr("Sort By");
            echo "</label>";

            echo "<select name=orderby onChange='javascript:changeSort(this)'>";

            $link = URL::Current();
            $link->setClearParams(Paginator::KEY_PAGE);
            $link->add(new URLParameter(Paginator::KEY_ORDER_BY, ""));
            $link->add(new URLParameter(Paginator::KEY_ORDER_DIR, ""));

            foreach ($sort_fields as $field_name => $sort_field) {

                $selected = "";

                if (strcmp($sort_field->value, $this->paginator->getOrderField()) == 0) {
                    $selected = " SELECTED ";
                }

                $link->get(Paginator::KEY_ORDER_BY)->setValue($sort_field->value);
                $link->get(Paginator::KEY_ORDER_DIR)->setValue($sort_field->order_direction);

                echo "<option $selected value='{$link->toString()}' >" . tr($sort_field->label) . "</option>";

            }
            echo "</select>";

            $active_field = $this->paginator->getOrderField();
            $active_direction = $this->paginator->getOrderDirection();

            $link->get(Paginator::KEY_ORDER_BY)->setValue($this->paginator->getOrderField());

            $direction = "ASC";
            if (strcmp($active_direction, "ASC") == 0) {
                $direction = "DESC";
            }
            $link->get(Paginator::KEY_ORDER_DIR)->setValue($direction);

            echo "<a class='direction' direction='$active_direction' href='{$link->toString()}'></a>";

        echo "</div>";

        ?>
        <script type='text/javascript'>
            function changeSort(sel) {
                var href = sel.options[sel.options.selectedIndex].value;
                window.location.href = href;
            }
        </script>
        <?php
    }

    protected function renderPageSelector() : void
    {

        $link = URL::Current();
        $link->add(new URLParameter(Paginator::KEY_PAGE));

        echo "<div class='pager'>";

        $a = $this->paginator->getPageListStart();

//        if ($this->paginator->havePreviousPage() || $this->paginator->haveNextPage()) {
//            echo "<label>" . tr("Page") . "</label>";
//        }

        if ($this->paginator->getCurrentPage() > 0) {

            $link->get(Paginator::KEY_PAGE)->setValue(0);
            echo "<a  href='{$link->toString()}' title='".tr("First")."'> <<  </a>";

            $link->get(Paginator::KEY_PAGE)->setValue($this->paginator->getCurrentPage() - 1);
            echo "<a   href='{$link->toString()}' title='".tr("Prev")."'> <  </a>";

        }

        while ($a < $this->paginator->getPageListEnd()) {

            $link->get(Paginator::KEY_PAGE)->setValue($a);

            $link_class = "";
            if ($this->paginator->getCurrentPage() == $a) {
                $link_class = "class=selected";
            }

            echo "<a $link_class  href='{$link->toString()}'>" . ($a + 1) . "</a>";
            $a++;
        }

        if (($this->paginator->getCurrentPage() + 1) < $this->paginator->getPagesTotal()) {

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->getCurrentPage() + 1));
            echo "<a  href='{$link->toString()}' title='".tr("Next")."'> > </a>";

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->getPagesTotal() - 1));
            echo "<a  href='{$link->toString()}' title='".tr("Last")."'> >> </a>";
        }

        echo "</div>";
    }
}

?>
