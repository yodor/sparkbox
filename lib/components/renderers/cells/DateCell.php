<?php
include_once("components/renderers/cells/TableCell.php");

class DateCell extends TableCell
{

    protected string $format = "j F Y\<\B\R\>H:i:s";

    protected TextComponent $dateText;

    public function __construct($format = "j F Y\<\B\R\>H:i:s")
    {
        parent::__construct();
        $this->format = $format;
    }

    public function setData(array $data): void
    {
        parent::setData($data);
        $value = $this->getContents();
        if (strcmp($value, "0000-00-00") == 0 || strlen($value) < 1) {
            $this->setContents("N/A");
        }
        else {
            $this->setContents(date($this->format, strtotime($value)));
        }
    }

}

?>