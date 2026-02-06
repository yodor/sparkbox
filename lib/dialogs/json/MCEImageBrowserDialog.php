<?php
include_once("dialogs/json/JSONDialog.php");
include_once("responders/json/MCEImageBrowserResponder.php");


class MCEImageBrowserDialog extends JSONDialog
{

    /**
     * @var InputComponent
     */
    protected InputComponent $icmp;

    protected ArrayDataInput $image_input;

    public function __construct()
    {
        parent::__construct();

        $this->setTitle("MCE Image Browser");
        $this->setType(MessageDialog::TYPE_PLAIN);

        $this->setSingleInstance(true);

        $this->setResponder(new MCEImageBrowserResponder());


        $this->image_input = DataInputFactory::Create(InputType::SESSION_IMAGE, "mceImage", "Upload Image", 1);
        $this->image_input->getProcessor()->setTransactBeanItemLimit(4);

        $session_image = $this->image_input->getRenderer();

        if ($session_image instanceof SessionImage) {
            $session_image->setResponder($this->responder);
        }

        $this->icmp = new InputComponent($this->image_input);

        $this->content->items()->append($this->icmp);

        $image_storage = new Container(false);
        $image_storage->setComponentClass("ImageStorage");
        $this->content->items()->append($image_storage);

        //imagedimensiondialog from javascript
        //TODO: make imagedimensiondialog
        new ConfirmMessageDialog();

    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/dialogs/ConfirmMessageDialog.js";
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/dialogs/json/MCEImageBrowserDialog.js";
        return $arr;
    }

    protected function initButtons() : void
    {
        $btn_close = new Button();
        $btn_close->setContents(tr("Close"));
        $btn_close->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $btn_close->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn_close);
    }


}

?>
