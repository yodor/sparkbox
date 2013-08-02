<?php
include_once ("lib/panels/MessageDialog.php");

class ConfirmMessageDialog extends MessageDialog
{

	public function __construct($title="Question!", $id = "confirm_dialog")
	{
		parent::__construct($title, $id);
		
		$this->show_close_button=FALSE;

		$this->buttons = Array();
		
		$btn_ok = StyledButton::DefaultButton();
		$btn_ok->setButtonType(StyledButton::TYPE_BUTTON);
		$btn_ok->setText("OK");
		$btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);
		
		$this->buttons[MessageDialog::BUTTON_ACTION_CONFIRM] = $btn_ok;
		
		$btn_cancel = StyledButton::DefaultButton();
		$btn_cancel->setButtonType(StyledButton::TYPE_BUTTON);
		$btn_cancel->setText("Cancel");
		$btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
		$this->buttons[MessageDialog::BUTTON_ACTION_CANCEL] = $btn_cancel;
	}
	
}
?>