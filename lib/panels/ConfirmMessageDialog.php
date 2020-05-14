<?php
include_once("panels/MessageDialog.php");

class ConfirmMessageDialog extends MessageDialog
{

    public function __construct($title = "Question!", $id = "confirm_dialog")
    {
        parent::__construct($title, $id);

        $this->show_close_button = FALSE;

        $this->buttons = array();

        $btn_ok = new ColorButton();
        $btn_ok->setText("OK");
        $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);

        $this->buttons[MessageDialog::BUTTON_ACTION_CONFIRM] = $btn_ok;

        $btn_cancel = new ColorButton();

        $btn_cancel->setText("Cancel");
        $btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $btn_cancel->setAttribute("default_action", 1);
        $this->buttons[MessageDialog::BUTTON_ACTION_CANCEL] = $btn_cancel;


        $this->component_class = "MessageDialog";
    }

}

?>
