<?php
include_once("input/renderers/DataIteratorField.php");
include_once("components/renderers/items/DataIteratorItem.php");

class RadioItem extends DataIteratorItem
{

    public function renderImpl()
    {
        //hackish! - force submit of unchecked checkbox
        //echo "<input type='hidden' name='{$this->name}' value=''>";

        $checked = ($this->selected) ? " CHECKED " : "";
        echo "<input type='radio' value='$this->value' name='$this->name' $checked >";
        echo "<span>$this->label</span>";
    }

}

class RadioField extends DataIteratorField
{

    protected bool $is_compound = true;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setItemRenderer(new RadioItem());
    }

}

?>
