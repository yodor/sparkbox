<?php
include_once("components/renderers/cells/TableCell.php");
include_once("input/renderers/CheckItem.php");

class CheckboxCell extends TableCell
{

    protected CheckItem $checkbox;

    public function __construct()
    {
        parent::__construct();
        $this->checkbox = new CheckItem();

        $this->items()->append($this->checkbox);
    }


    public function setData(array $data) : void
    {
        parent::setData($data);

        $value = $data[$this->column->getName()] ?? "";

        $this->checkbox->setValue($value);
        $this->checkbox->setName($this->column->getName()."[]");
        //TODO: set selected

        $this->setContents("");

    }
}

?>
