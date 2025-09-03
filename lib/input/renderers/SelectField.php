<?php
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/SelectItem.php");

class SelectField extends DataIteratorField
{

    protected ?string $default_label = null;
    protected string $default_value = "";

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new SelectItem());

        $this->items()->clear();

        $this->input = new Input();
        $this->input->setTagName("select");
        $this->input->setClosingTagRequired(TRUE);

        $this->input->items()->append(new ClosureComponent($this->renderItems(...), false));

        $this->items()->append($this->input);

        $this->setDefaultOption("--- SELECT ---");
    }

    public function setDefaultOption(string|null $label, string $value="") : void
    {
        $this->default_label = $label;
        $this->default_value = $value;
    }

    protected function renderDefaultItem() : void
    {
        //prepare the default select value
        if (!is_null($this->default_label)) {
            $this->item->setID(-1);
            $this->item->setKey(-1);

            $this->item->setValue($this->default_value);
            $this->item->setLabel($this->default_label);

            $this->item->setSelected($this->isModelSelected((string)$this->item->getValue()));

            $this->item->render();
        }
    }

    protected function renderItems() : void
    {
        $this->renderDefaultItem();
        parent::renderItems();
    }

    protected function isModelSelected(string $item_value) : bool
    {
        $field_values = $this->dataInput->getValue();
        $selected = FALSE;
        if (is_array($field_values)) {
            foreach ($field_values as $idx => $field_value) {
                $selected = $this->compareValue($item_value, (string)$field_value);
                if ($selected) break;
            }
        }
        else {
            $selected = $this->compareValue($item_value, (string)$field_values);
        }
        return $selected;
    }

    protected function compareValue(string $item_value, string $field_value) : bool
    {
        return (strcmp($item_value, $field_value)==0);
    }

}

class SelectMultipleField extends SelectField
{
    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        //use SelectField css
        $this->addClassName("SelectField");

        $this->input->setAttribute("multiple", "");

        //do not render default inital option
        $this->setDefaultOption(null);
    }

    protected function processInput(): void
    {
        parent::processInput();

        //dataInput name might already be appended with []
        //make the name an array as this is multi-select
        $this->input->setName($this->dataInput->getName()."[]");
    }



}

?>
