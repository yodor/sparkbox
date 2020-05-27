<?php
include_once("components/Component.php");
include_once("components/TableColumn.php");

class TableCellRenderer extends Component implements IDataResultProcessor
{
    protected $tagName = "TD";

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
        echo $this->value;
    }

    public function setColumn(TableColumn $tc)
    {
        $this->column = $tc;

        $this->field = $tc->getFieldName();

        $this->setAttribute("column", $this->field);
        $this->setAttribute("title", $tc->getLabel());
    }

    public function setData(array &$data)
    {
        //debug("setData: ", $data);

        foreach ($this->value_attributes as $idx => $field) {
            if (isset($data[$field])) {
                $this->setAttribute($field, $data[$field]);
            }
        }

        if (isset($data[$this->tooltip_field])) {
            $this->setTooltipText($data[$this->tooltip_field]);
        }

        if (isset($data[$this->field])) {
            $this->value = $data[$this->field];
        }
        else {
            $this->value = "";
        }

    }

    public function setTooltipFromField(string $field)
    {
        $this->tooltip_field = $field;
    }

}

?>