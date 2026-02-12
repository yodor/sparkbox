<?php
include_once("components/Script.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/renderers/IPageScript.php");

abstract class PageScript extends Script implements IPageComponent, IPageScript
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the script code text to be rendered inside the script tag inner contents
     * @return string
     */
    abstract function code() : string;

    protected function renderImpl(): void
    {
        $this->buffer()->set($this->code());
        parent::renderImpl();
    }
}