<?php
include_once("dialogs/MessageDialog.php");

class ConfirmMessageDialog extends MessageDialog
{

    public function __construct()
    {
        parent::__construct();
        $this->setType(MessageDialog::TYPE_QUESTION);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/ConfirmMessageDialog.js";
        return $arr;
    }

    protected function initButtons() : void
    {
        $btn_ok = new Button();
        $btn_ok->setContents("OK");
        $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);
        $btn_ok->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn_ok);

        $btn_cancel = new Button();
        $btn_cancel->setContents("Cancel");
        $btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $this->buttonsBar->items()->append($btn_cancel);
    }

}

?>
