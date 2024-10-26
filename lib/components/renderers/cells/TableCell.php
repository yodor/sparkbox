<?php
include_once("components/Component.php");
include_once("components/TableColumn.php");

class TableCell extends Container implements IDataResultProcessor
{

    /**
     * @var Action|null
     */
    protected ?Action $action = null;

    /**
     * @var TableColumn|null
     */
    protected ?TableColumn $column = null;

    protected array $dataAttributes = array();

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("Cell");
    }

    public function setAction(Action $a): void
    {
        $this->action = $a;
    }

    public function setColumn(TableColumn $tc): void
    {
        $this->column = $tc;
    }

    public function setData(array $data) : void
    {
        $this->setContents($data[$this->column->getName()] ?? "");

        foreach ($this->dataAttributes as $name => $value) {
            if (isset($data[$name])) {
                $this->setAttribute($name, $data[$name]);
            }
            else {
                $this->removeAttribute($name);
            }
        }
    }

    /**
     * Set attribute from datarow key_name
     * @param $key_name
     */
    public function addDataAttribute(string $name) : void
    {
        $this->dataAttributes[$name] = 1;
    }
}

?>
