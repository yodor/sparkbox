<?php
include_once("input/renderers/DataIteratorField.php");
include_once("components/renderers/items/DataIteratorItem.php");

class SelectOption extends DataIteratorItem
{
    public function __construct()
    {
        parent::__construct();
        $this->tagName = "OPTION";
    }

    public function setSelected(bool $mode) : void
    {
        parent::setSelected($mode);
        if ($mode) {
            $this->setAttribute("SELECTED", "");
        }
        else {
            $this->removeAttribute("SELECTED");
        }
    }

    public function processAttributes()
    {
        $this->setAttribute("value", (string)$this->value);
        if ($this->isSelected()) {
            $this->setAttribute("SELECTED", "");
        }
        else {
            $this->removeAttribute("SELECTED");
        }
    }

    public function renderImpl()
    {
        echo $this->label;
    }

}

class SelectField extends DataIteratorField
{

    public $na_label = "--- SELECT ---";
    public $na_value = NULL;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new SelectOption());
    }

    protected function startRenderItems()
    {

        parent::startRenderItems();

        $attrs = $this->prepareInputAttributes();

        echo "<select $attrs>";

        //prepare the default select value
        if ($this->na_label) {

            //            $data = array($this->getItemRenderer()->getValueKey()=>$this->na_value,
            //                $this->getItemRenderer()->getLabelKey()=>$this->na_label);

            $item = $this->item;

            $item->setID(-1);

            $item->setIndex(-1);

            //$item->setData($data);

            $item->setValue($this->na_value);
            $item->setLabel($this->na_label);

            $item->setSelected($this->isModelSelected());

            $item->render();

        }
    }

    protected function finishRenderItems()
    {

        echo "</select>";
        parent::finishRenderItems();
    }

    protected function isModelSelected(): bool
    {
        $field_values = $this->input->getValue();
        $selected = FALSE;
        if (is_array($field_values)) {
            foreach ($field_values as $idx => $field_value) {
                $selected = $this->compareValue((string)$field_value);
                if ($selected) break;
            }
        }
        else {
            $selected = $this->compareValue((string)$field_values);
        }
        return $selected;
    }

    protected function compareValue(string $field_value) : bool
    {
        return (strcmp((string)$this->item->getValue(), $field_value)==0);
    }

}

class SelectMultipleField extends SelectField
{
    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setInputAttribute("multiple", "");

        $this->na_label = "";
    }

    protected function startRenderItems()
    {
        $this->setInputAttribute("name", $this->input->getName() . "[]");
        parent::startRenderItems();
    }

}

?>
