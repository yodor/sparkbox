<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class CheckboxCellRenderer extends TableCellRenderer
{
    protected $field = "";

    public function __construct($field = "")
    {
        parent::__construct();

        $this->field = $field;

    }

    public function setColumn(TableColumn $tc)
    {
        parent::setColumn($tc);
        $tc->setAlignClass(TableColumn::ALIGN_CENTER);
    }

    protected function renderImpl()
    {
        echo "<div class='value'>";
        echo "<input type=checkbox name='select_{$this->field}[]'  value='" . attributeValue($this->value) . "'>";
        echo "<span>$this->value</span>";
        echo "</div>";
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        if (!$this->field) {
            $this->field = $this->column->getFieldName();
        }

        $this->value = $data[$this->field];

    }
}

?>
