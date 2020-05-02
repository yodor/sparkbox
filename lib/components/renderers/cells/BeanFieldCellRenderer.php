<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/ICellRenderer.php");
include_once("lib/components/TableColumn.php");

class BeanFieldCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $bean = false;
    protected $field_name = false;

    public function __construct(DBTableBean $bean, $field_name)
    {
        parent::__construct();

        $this->bean = $bean;
        $this->field_name = $field_name;
    }

    public function renderCell(array &$row, TableColumn $tc)
    {
        $this->processAttributes($row, $tc);

        $this->startRender();
        $field_key = $tc->getFieldName();

        $qry = $this->bean->queryField($field_key, $row[$field_key], 1);

        $qry->exec();

        if ($brow = $qry->next()) {
            echo $brow[$this->field_name];
        }

        $this->finishRender();
    }
}

?>