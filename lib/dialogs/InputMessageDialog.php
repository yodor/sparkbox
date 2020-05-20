<?php
include_once ("dialogs/ConfirmMessageDialog.php");

class InputMessageDialog extends ConfirmMessageDialog
{
    protected $input;

    public function __construct($title = "Message", $id = "input_dialog")
    {
        parent::__construct($title, $id);
        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $this->input = DataInputFactory::Create(DataInputFactory::TEXT, "user_input", "Input Data", 0);
    }

    protected function renderImpl()
    {
        $cmp = new InputComponent($this->input);
        $cmp->render();
    }
}