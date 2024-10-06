<?php
include_once("components/Script.php");

class PageScript extends Script implements IPageComponent
{

    public function __construct()
    {
        parent::__construct();
        $this->translation_enabled = false;
    }

    /**
     * Return the script code text to be rendered inside the script tag inner contents
     * @return string
     */
    protected function code() : string
    {
        return "";
    }

    protected function renderImpl()
    {
        $this->buffer()->set($this->code());
        parent::renderImpl();
    }
}
?>
