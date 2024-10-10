<?php
include_once("dialogs/ConfirmMessageDialog.php");

class JSONDialog extends ConfirmMessageDialog
{
    protected ?JSONResponder $responder = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType(MessageDialog::TYPE_PLAIN);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/JSONDialog.js";
        return $arr;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $this->setAttribute(RequestResponder::KEY_COMMAND, $this->responder?->getName());

    }

    public function getResponder() : JSONResponder
    {
        return $this->responder;
    }

    public function setResponder(JSONResponder $responder) : void
    {
        $this->responder = $responder;
    }
}

?>