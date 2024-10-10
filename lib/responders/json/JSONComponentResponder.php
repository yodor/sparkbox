<?php
include_once("responders/json/JSONResponder.php");

class JSONComponentResponder extends JSONResponder
{

    /**
     * @var Component
     */
    protected Component $component;

    public function __construct(string $name)
    {
        SparkObject::setName($name);
        parent::__construct();
    }

    public function setComponent(Component $component) : void
    {
        $this->component = $component;
    }

    public function _render(JSONResponse $resp)
    {
        $this->component->render();
    }

}
?>