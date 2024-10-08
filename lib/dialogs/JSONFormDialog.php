<?php
include_once("dialogs/ConfirmMessageDialog.php");

class JSONFormDialog extends ConfirmMessageDialog {

    protected string $templateID = "json_dialog";

    public function __construct()
    {
        parent::__construct();

        $button_confirm = $this->buttonsBar->items()->getByAction(MessageDialog::BUTTON_ACTION_CONFIRM);
        if ($button_confirm instanceof Button) {
            $button_confirm->setContents("Submit");
        }
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

}

?>
