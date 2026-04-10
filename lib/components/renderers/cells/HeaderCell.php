<?php
include_once("components/renderers/cells/TableCell.php");

class HeaderCell extends TableCell implements IGETConsumer
{

    protected Action $orderDirection;
    protected Action $label;

    public function __construct()
    {
        parent::__construct();
        $this->addClassName("Header");

        $this->label = new Action();
        $this->label->translation_enabled = true;
        $this->items()->append($this->label);

        $this->orderDirection = new Action();
        $this->orderDirection->setComponentClass("direction");

        $this->items()->append($this->orderDirection);

    }

    protected function finalize(): void
    {
        parent::finalize();

        $this->addClassName($this->column->getAlignClass());

        $this->label->setContents($this->column->getLabel());

        if (!$this->column->isSortable()) {
            $this->label->setURL(new URL());
            $this->orderDirection->setRenderEnabled(false);
            return;
        }

        $url = SparkPage::Instance()->currentURL();
        $url->add(new URLParameter(Paginator::KEY_ORDER_BY, $this->column->getName()));
        $url->add(new URLParameter(Paginator::KEY_ORDER_DIR, OrderDirection::ASC->value));

        if (Spark::strcmp_isset(Paginator::KEY_ORDER_BY, $this->column->getName())) {

            $this->orderDirection->setRenderEnabled(true);

            $direction = OrderDirection::tryFrom($_GET[Paginator::KEY_ORDER_DIR]??"");
            if (!isset($direction)) {
                $direction = OrderDirection::ASC;
            }

            //current list is ordered ASC show up arrow and href with opposite direction
            $url->get(Paginator::KEY_ORDER_DIR)->setValue($direction->opposite()->value);
            $this->orderDirection->setAttribute("direction", $direction->value);
            $this->orderDirection->setURL($url);

        }
        else {
            $this->orderDirection->setRenderEnabled(false);
        }

        $this->label->setURL($url);
    }


    /**
     * @return array The parameter names this object is interacting with
     */
    public function getParameterNames(): array
    {
        return array(Paginator::KEY_ORDER_BY, Paginator::KEY_ORDER_DIR);
    }
}