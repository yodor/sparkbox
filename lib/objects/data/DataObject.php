<?php
include_once ("utils/IDataResultProcessor.php");

class DataObject extends SparkObject implements IDataResultProcessor
{

    protected $value = "";

    /**
     * DataObject constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setData(array $data) : void
    {
        $this->value = $data[$this->name] ?? "";

    }
}
?>
