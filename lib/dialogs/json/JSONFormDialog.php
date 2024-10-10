<?php
include_once("dialogs/json/JSONDialog.php");

class JSONFormDialog extends JSONDialog {


    public function __construct()
    {
        parent::__construct();
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

    protected function initButtons(): void
    {
        parent::initButtons();

        $button_confirm = $this->buttonsBar->items()->getByAction(MessageDialog::BUTTON_ACTION_CONFIRM);
        if ($button_confirm instanceof Button) {
            $button_confirm->setContents(tr("Submit"));
        }
    }
}

?>
