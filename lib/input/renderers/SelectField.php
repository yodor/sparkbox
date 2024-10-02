<?php
include_once("input/renderers/DataIteratorField.php");
include_once("input/renderers/SelectItem.php");

class SelectField extends DataIteratorField
{

    public $na_label = "--- SELECT ---";
    public $na_value = NULL;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new SelectItem());

        $this->items()->clear();

        $this->input = new Input();
        $this->input->setTagName("SELECT");
        $this->input->setClosingTagRequired(TRUE);

//        $this->elements->items()->clear();
//        $this->elements->items()->append($this->input);

        $this->input->items()->append(new ClosureComponent($this->renderItems(...), false));

        $this->items()->append($this->input);
    }

    protected function renderItems() : void
    {
        //prepare the default select value
        if ($this->na_label) {

            $this->item->setID(-1);
            $this->item->setIndex(-1);

            $this->item->setValue($this->na_value);
            $this->item->setLabel($this->na_label);

            $this->item->setSelected($this->isModelSelected((string)$this->item->getValue()));

            $this->item->render();

        }
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
        $this->setClassName("SelectField");

        $this->input->setAttribute("multiple", "");

        $this->na_label = "";
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->input->setName($this->dataInput->getName()."[]");
    }

}

?>
