<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/TableColumn.php");

class BooleanCellRenderer extends TableCellRenderer
{

    protected string $true_value = "Enabled";
    protected string $false_value = "Disabled";

    public function __construct(string $true_value = "Enabled", string $false_value = "Disabled")
    {
        parent::__construct();

        $this->true_value = $true_value;
        $this->false_value = $false_value;
    }

    public function setColumn(TableColumn $tc)
    {
        parent::setColumn($tc);
        $tc->setAlignClass(TableColumn::ALIGN_CENTER);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        $this->value = ($data[$this->column->getFieldName()]) ? tr($this->true_value) : tr($this->false_value);

    }
}

?>
