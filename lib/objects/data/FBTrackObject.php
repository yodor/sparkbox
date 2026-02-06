<?php
include_once("objects/data/DataObject.php");

//https://developers.facebook.com/docs/facebook-pixel/reference

class FBTrackObject extends DataObject
{

    protected string $event;
    protected string $param_template;
    protected string $dataKey;

    protected string $parameters;

    public function __construct()
    {
        parent::__construct();
    }

    //ViewContent
    public function setEvent(string $event) : void
    {
        $this->event = $event;
    }

    public function getEvent() : string
    {
        return $this->event;
    }

    /**
     * Set template for parameters. %$this->dataKey% would be replaced with value of $data[$this->dataKey] during setData
     * @param string $value
     */
    public function setParamTemplate(string $value) : void
    {
        $this->param_template = $value;
    }

    public function getParamTemplate() : string
    {
        return $this->param_template;
    }

    public function getParameters() : string
    {
        return $this->parameters;
    }

    public function useDataKey(string $dataKey) : void
    {
        $this->dataKey = $dataKey;
    }

    public function dataKey() : string
    {
        return $this->dataKey;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        if (!isset($data[$this->dataKey])) throw new Exception("Data key '$this->dataKey' not found");

        $replace = array("%" . $this->dataKey . "%" => Spark::AttributeValue($data[$this->dataKey]));
        $this->parameters = strtr($this->param_template, $replace);

    }

}
