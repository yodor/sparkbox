<?php
include_once("responders/json/JSONResponder.php");

class JSONComponentResponder extends JSONResponder
{

    /**
     * @var Component
     */
    protected Component $component;

    public function __construct(string $cmd)
    {
        parent::__construct($cmd);

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