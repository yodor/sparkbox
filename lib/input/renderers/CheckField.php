<?php
include_once("lib/input/renderers/DataSourceField.php");
include_once("lib/input/renderers/DataSourceItem.php");

class CheckItem extends DataSourceItem
{

    public function renderImpl()
    {

        //hackish! - force submit of unchecked checkbox
        echo "<input type='hidden' name='{$this->name}' value=''>";


        echo "<input type='checkbox' value='{$this->value}' name='{$this->name}' id='{$this->id}' {$this->user_attributes}";
        if ($this->isSelected()) echo "CHECKED";

        echo ">";
        echo "<span>{$this->label}</span>";
    }

}


class CheckField extends DataSourceField
{

    public function __construct()
    {
        parent::__construct();
        $this->setItemRenderer(new CheckItem());

    }

    public function renderImpl()
    {
        $field_values = $this->field->getValue();


        $field_name = $this->field->getName();

        $field_attr = $this->prepareFieldAttributes();


        parent::renderImpl();

        if (!($this->data_bean instanceof IDataBean)) {

            $item = clone $this->item;

            $item->setValue(1);

//            if (strlen($item->getLabel())) {
//
//            }
//            else {
//                $item->setLabel($this->caption);
//            }
            $item->setName($field_name);

            $item->setSelected($field_values);

            $item->setUserAttributes($field_attr);

            echo "<div class='FieldElements'>";
            $item->render();
            echo "</div>";
        }

    }

    public function renderValueImpl()
    {
        $field_value = $this->field->getValue();

        if (!($this->data_bean instanceof IDataBean)) {

            if ($field_value > 0) {
                echo tr("Yes");
            }
            else {
                echo tr("No");
            }

        }
        else {
            parent::renderValueImpl();
        }
    }
}

?>
