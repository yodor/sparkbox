<?php
include_once ("dialogs/ConfirmMessageDialog.php");

class InputMessageDialog extends ConfirmMessageDialog
{
    /**
     * @var ArrayDataInput|DataInput
     */
    protected $input;

    public function __construct($title = "Message", $id = "input_dialog")
    {
        parent::__construct($title, $id);
        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $this->input = DataInputFactory::Create(DataInputFactory::TEXT, "user_input", "Input Data", 0);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/InputMessageDialog.js";
        return $arr;
    }


    public function getInput() : DataInput
    {
        return $this->input;
    }

    protected function renderImpl()
    {
        $cmp = new InputComponent($this->input);
        $cmp->render();
    }
}