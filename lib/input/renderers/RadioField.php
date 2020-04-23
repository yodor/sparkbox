<?php
include_once("lib/input/renderers/InputField.php");
include_once("lib/components/Component.php");

class RadioItem extends DataSourceItem
{


    public function renderImpl()
    {
        echo "<input type='radio' value='{$this->value}' name='{$this->name}' id='{$this->id}' ";
        if ($this->isSelected()) echo "CHECKED ";
        echo ">";
        echo "<span>{$this->label}</span>";
    }

}

class RadioField extends DataSourceField
{

    public function __construct()
    {
        parent::__construct();

        $this->setItemRenderer(new RadioItem());

        $this->is_compound = true;

    }

}

?>