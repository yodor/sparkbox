<?php
include_once("input/renderers/InputField.php");
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/DataIteratorItem.php");

class SelectOption extends DataIteratorItem
{
    public function __construct()
    {
        parent::__construct();
    }

    public function startRender()
    {
        $attribs = $this->prepareAttributes();

        echo "<option value='{$this->value}' $attribs ";
        if ($this->selected) echo "SELECTED";
        echo ">";
    }

    public function finishRender()
    {
        echo "</option>";
    }

    public function renderImpl()
    {
        echo $this->label;
    }

    protected function isModelSelected(DataInput $input) : bool
    {
        $field_values = $input->getValue();
        $selected = FALSE;
        if (is_array($field_values)) {
            foreach ($field_values as $idx => $field_value) {
                if (strcmp($this->value, $field_value) == 0) {
                    $selected = TRUE;
                    break;
                }
            }
        }
        else {
            if (strcmp($this->value, $field_values) == 0) {
                $selected = TRUE;
            }
        }
        return $selected;
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
        echo "<select $attrs >";

        //prepare the default select value
        if ($this->na_label) {

            $data = array($this->getItemRenderer()->getValueKey()=>$this->na_value,
                $this->getItemRenderer()->getLabelKey()=>$this->na_label);

            //$item = clone $this->item;
            $item = $this->item;
            $item->setID(-1);
            //$item->setValue($this->na_value);
            //$item->setLabel($this->na_label);
            $item->setIndex(-1);

            //$selected = $this->isModelSelected($this->na_value, $this->input->getValue());

            //$item->setSelected($selected);
            $item->setData($data, $this->input);

            $item->render();

        }
    }

    protected function finishRenderItems()
    {

        echo "</select>";
        parent::finishRenderItems();
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
