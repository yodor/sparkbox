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

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $this->addClassName($this->column->getAlignClass());

        $this->label->setContents($this->column->getLabel());

        if (!$this->column->isSortable()) {
            $this->label->setURL(new URL());
            $this->orderDirection->setRenderEnabled(false);
            return;
        }

        $url = URL::Current();
        $url->add(new URLParameter(Paginator::KEY_ORDER_BY, $this->column->getName()));
        $url->add(new URLParameter(Paginator::KEY_ORDER_DIR, "ASC"));

        if (Spark::strcmp_isset(Paginator::KEY_ORDER_BY, $this->column->getName())) {

            $this->orderDirection->setRenderEnabled(true);

            //show the arrow link to change order direction
            if (Spark::strcmp_isset(Paginator::KEY_ORDER_DIR, "ASC")) {

                //current list is ordered ASC show up arrow and href with opposite direction
                $url->get(Paginator::KEY_ORDER_DIR)->setValue("DESC");
                $this->orderDirection->setAttribute("direction", "ASC");
                $this->orderDirection->setURL($url);

            }
            else {

                $url->get(Paginator::KEY_ORDER_DIR)->setValue("ASC");
                $this->orderDirection->setAttribute("direction", "DESC");
                $this->orderDirection->setURL($url);

            }

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

?>
