<?php
include_once("input/renderers/InputField.php");
include_once("components/Component.php");

class RadioItem extends DataSourceItem
{


    public function renderImpl()
    {
        //hackish! - force submit of unchecked checkbox
        //echo "<input type='hidden' name='{$this->name}' value=''>";

        echo "<input type='radio' value='{$this->value}' name='{$this->name}'  ";
        if ($this->isSelected()) echo "CHECKED ";
        echo ">";
        echo "<span>{$this->label}</span>";
    }

}

class RadioField extends DataSourceField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setItemRenderer(new RadioItem());

        $this->is_compound = true;

    }

}

?>