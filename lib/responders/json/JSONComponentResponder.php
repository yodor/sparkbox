<?php
include_once("responders/json/JSONResponder.php");
include_once("dialogs/JSONFormDialog.php");

class JSONComponentResponder extends JSONResponder
{

    /**
     * @var Component
     */
    protected $component;

    public function __construct(string $cmd)
    {
        parent::__construct($cmd);
        $dialog = new JSONFormDialog();
    }

    public function setComponent(Component $component)
    {
        $this->component = $component;
    }

    public function _render(JSONResponse $resp)
    {
        $this->component->render();
    }

}
?>