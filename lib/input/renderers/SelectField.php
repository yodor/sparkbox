<?php
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/SelectItem.php");

class SelectField extends DataIteratorField
{

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

        $this->input->setAttribute("multiple");

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