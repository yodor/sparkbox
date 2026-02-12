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

        $this->true_value = $true_value;
        $this->false_value = $false_value;

        $this->addClassName("Boolean");

    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->setContents( ($data[$this->column->getName()]) ? tr($this->true_value) : tr($this->false_value));

    }
}