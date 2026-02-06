<?php
include_once ("utils/IDataResultProcessor.php");

class DataObject extends SparkObject implements IDataResultProcessor
{

    protected mixed $value = "";
    protected string $valueKey = "";

    /**
     * DataObject constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getValue() : mixed
    {
        return $this->value;
    }

    public function setValue($value) : void
    {
        $this->value = $value;
    }

    public function setValueKey(string $valueKey) : void
    {
        $this->valueKey = $valueKey;
    }

    public function getValueKey() : string
    {
        return $this->valueKey;
    }

    public function setData(array $data) : void
    {
        $key = $this->name;
        if ($this->valueKey) {
            $key = $this->valueKey;
        }
        $this->value = $data[$key] ?? "";
    }
}
?>
