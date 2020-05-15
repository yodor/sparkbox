<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class BeanFieldCellRenderer extends TableCellRenderer
{

    protected $bean = FALSE;
    protected $field_name = FALSE;

    public function __construct(DBTableBean $bean, string $field_name)
    {
        parent::__construct();

        $this->bean = $bean;
        $this->field_name = $field_name;
    }

    public function setData(array &$row)
    {
        parent::setData($row);

        $field_key = $this->column->getFieldName();

        $qry = $this->bean->queryField($field_key, $row[$field_key], 1);
        $qry->select->fields = $this->field_name;
        $qry->exec();

        if ($brow = $qry->next()) {
            $this->value = $brow[$this->field_name];
        }
    }
}

?>