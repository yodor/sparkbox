<?php
include_once("dialogs/ConfirmMessageDialog.php");

class JSONFormDialog extends ConfirmMessageDialog {

    public function __construct($title = "Question", $id = "json_dialog")
    {
        parent::__construct($title, $id);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/JSONFormDialog.js";
        return $arr;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/JSONFormDialog.css";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_ok = new Button();
        $btn_ok->setContents("Submit");
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
