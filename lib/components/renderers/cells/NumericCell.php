<?php
include_once("components/renderers/cells/TableCell.php");

class NumericCell extends TableCell
{

    protected string $format = "%01.2f";

    public function __construct(string $format = "%01.2f")
    {
        parent::__construct();
        $this->format = $format;
        $this->addClassName("Numeric");
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->setContents(sprintf($this->format, $this->getContents()));
    }

}