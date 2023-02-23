<?php
include_once ("utils/IDataResultProcessor.php");

class DataObject  implements IDataResultProcessor
{
    protected $name = "";
    protected $value = "";

    /**
     * DataObject constructor.
     */
    public function __construct()
    {

    }

    /**
     * Set this data object name
     * the value of this object is set during setData by taking the value of $data[$name]
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setData(array &$data)
    {
        if (isset($data[$this->name])) {
            $this->value = $data[$this->name];
        }
        else {
            $this->value = "";
        }
    }
}
?>