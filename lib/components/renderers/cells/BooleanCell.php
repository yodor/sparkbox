<?php
include_once("components/renderers/cells/TableCell.php");
include_once("components/TableColumn.php");

class BooleanCell extends TableCell
{

    protected string $true_value = "Enabled";
    protected string $false_value = "Disabled";

    public function __construct(string $true_value = "Enabled", string $false_value = "Disabled")
    {
        parent::__construct();

        $this->true_value = tr($true_value);
        $this->false_value = tr($false_value);

        $this->addClassName("Boolean");

    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $value = $data[$this->column->getName()]??false;
        $this->setContents( ($value) ? $this->true_value : $this->false_value );

    }
}