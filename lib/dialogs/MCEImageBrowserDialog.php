<?php
include_once("dialogs/MessageDialog.php");
include_once("responders/json/MCEImageBrowserResponder.php");
include_once("dialogs/ConfirmMessageDialog.php");

class MCEImageBrowserDialog extends MessageDialog
{

    /**
     * @var MCEImageBrowserResponder
     */
    protected UploadControlResponder $handler;

    /**
     * @var InputComponent
     */
    protected InputComponent $icmp;

    protected ArrayDataInput $image_input;

    public function __construct()
    {
        parent::__construct("MCE Image Browser", "mceImage_browser");

        $this->handler = new MCEImageBrowserResponder();

        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $this->image_input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "mceImage", "Upload Image", 1);

        $this->image_input->getProcessor()->setTransactBeanItemLimit(4);

        $session_image = $this->image_input->getRenderer();

        if ($session_image instanceof SessionImage) {
            $session_image->setResponder($this->handler);
        }

        $this->icmp = new InputComponent($this->image_input);

        //imagedimensiondialog from javascript
        new ConfirmMessageDialog();

        $this->setAttribute('single',true);
    }

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/ConfirmMessageDialog.js";
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/MCEImageBrowserDialog.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_close = new Button();
        $btn_close->setContents("Close");
        $btn_close->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $btn_close->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn_close);
    }

    public function getHandler() : UploadControlResponder
    {
        return $this->handler;
    }

    public function setHandler(UploadControlResponder $handler) : void
    {
        $this->handler = $handler;
    }

    //final method
    protected function renderImpl()
    {

        echo "<form method='post' enctype='multipart/form-data'>";
        $this->icmp->render();
        echo "</form>";

        echo "<div class='ImageStorage'>";

        echo "<div class='Viewport'>";

        echo "<div class='Collection'></div>";

        echo "</div>";

        echo "</div>";

    }

}

?>
