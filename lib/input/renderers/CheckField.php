<?php
include_once("input/renderers/DataIteratorField.php");
include_once("components/renderers/items/DataIteratorItem.php");

class CheckItem extends DataIteratorItem
{

    public function renderImpl()
    {
        //TODO: find a way to set individual attributes
        $attrs = $this->prepareAttributes();

        //hackish! - force submit of unchecked checkbox
        echo "<input type='hidden' name='{$this->name}' value=''>";

        echo "<input type='checkbox' value='{$this->value}' name='{$this->name}' $attrs ";
        if ($this->isSelected()) echo "CHECKED";

        echo ">";
        echo "<span>{$this->label}</span>";
    }

}

class CheckField extends DataIteratorField
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);
        $this->setItemRenderer(new CheckItem());
    }

    public function renderImpl()
    {

        if (!$this->iterator) {

            $item = clone $this->item;

            $item->setValue(1);
            $item->setName($this->input->getName());
            $item->setSelected($this->input->getValue() ? TRUE : FALSE);

            echo "<div class='FieldElements'>";
            $item->render();
            echo "</div>";

        }
        else {
            parent::renderImpl();
        }

    }

}

?>
