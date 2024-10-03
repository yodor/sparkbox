<?php
include_once("components/Component.php");
include_once("components/TableColumn.php");

class TableCellRenderer extends Component implements IDataResultProcessor
{


    protected $tooltip_field = "";

    /**
     * @var Action
     */
    protected $action;

    protected $value_attributes = array();

    protected $value = "";

    /**
     * @var TableColumn
     */
    protected $column;

    protected $field = "";

    protected $sortable = TRUE;

    public function __construct()
    {
        parent::__construct();
        $this->tagName = "TD";
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $mode)
    {
        $this->sortable = $mode;
    }

    public function setAction(Action $a)
    {
        $this->action = $a;
    }

    /**
     * Set attribute from datarow key_name
     * @param $key_name
     */
    public function addValueAttribute(string $field)
    {
        $this->value_attributes[] = $field;
    }

    protected function renderImpl()
    {
        if ($this->translation_enabled) {
            echo tr($this->value);
        }
        else {
            echo $this->value;
        }
    }

    public function setColumn(TableColumn $tc)
    {
        $this->column = $tc;

        $this->field = $tc->getFieldName();

        $this->setAttribute("column", $this->field);
    }

    public function setData(array $data) : void
    {
        //debug("setData: ", $data);
        $this->value = $data[$this->field] ?? "";
        $this->setTooltip($data[$this->tooltip_field] ?? "");

        foreach ($this->value_attributes as $idx => $field) {
            if (isset($data[$field])) {
                $this->setAttribute($field, $data[$field]);
            }
            else {
                $this->removeAttribute($field);
            }
        }
    }

    public function setTooltipFromField(string $field)
    {
        $this->tooltip_field = $field;
    }

    public function getValue()
    {
        return $this->value;
    }

}

?>
