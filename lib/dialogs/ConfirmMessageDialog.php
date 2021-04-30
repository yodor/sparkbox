<?php
include_once("dialogs/MessageDialog.php");

class ConfirmMessageDialog extends MessageDialog
{

    public function __construct($title = "Question", $id = "confirm_dialog")
    {
        parent::__construct($title, $id);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/ConfirmMessageDialog.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_ok = new ColorButton();
        $btn_ok->setContents("OK");
        $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);
        $btn_ok->setAttribute("default_action", 1);
        $this->buttonsBar->append($btn_ok);

        $btn_cancel = new ColorButton();
        $btn_cancel->setContents("Cancel");
        $btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $this->buttonsBar->append($btn_cancel);
    }

}

?>