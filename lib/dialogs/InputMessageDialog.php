<?php
include_once ("dialogs/ConfirmMessageDialog.php");

class InputMessageDialog extends ConfirmMessageDialog
{

    protected DataInput $input;

    public function __construct()
    {
        parent::__construct();
        $this->setType(MessageDialog::TYPE_PLAIN);

        $this->input = DataInputFactory::Create(InputType::TEXT, "user_input", "Input Data", 0);

        $cmp = new InputComponent($this->input);
        $this->content->items()->append($cmp);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/dialogs/InputMessageDialog.js";
        return $arr;
    }


    public function getInput() : DataInput
    {
        return $this->input;
    }


}

?>