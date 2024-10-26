<?php
include_once("components/renderers/cells/TableCell.php");
include_once("input/renderers/Input.php");

class ColorCodeCell extends TableCell
{

    protected Input $input;

    public function __construct()
    {
        parent::__construct();
        $this->input = new Input("color");
        $this->items()->append($this->input);

    }

    public function setData(array $data): void
    {
        parent::setData($data);
        $this->input->setValue($this->getContents());
        $this->setContents("");


    }

}

?>
