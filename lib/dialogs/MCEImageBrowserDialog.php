<?php
include_once("dialogs/MessageDialog.php");
include_once("responders/json/MCEImageBrowserResponder.php");

class MCEImageBrowserDialog extends MessageDialog
{

    /**
     * @var MCEImageBrowserResponder
     */
    protected $handler;

    /**
     * @var InputComponent
     */
    protected $icmp;

    protected $image_input;

    public function __construct()
    {
        parent::__construct("MCE Image Browser", "mceImage_browser");

        $this->handler = new MCEImageBrowserResponder();

        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $this->image_input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "mceImage", "Upload Image", 1);
        $this->image_input->getRenderer()->assignUploadHandler($this->handler);

        $this->icmp = new InputComponent($this->image_input);

    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/popups/MCEImageBrowserDialog.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_close = new ColorButton();
        $btn_close->setContents("Close");
        $btn_close->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $btn_close->setAttribute("default_action", 1);
        $this->buttonsBar->append($btn_close);
    }

    public function getHandler() : UploadControlResponder
    {
        return $this->handler;
    }

    public function setHandler(UploadControlResponder $handler)
    {
        $this->handler = $handler;
    }

    //final method
    public function renderImpl()
    {

        echo "<form method='post' enctype='multipart/form-data'>";
        $this->icmp->render();
        echo "</form>";

        echo "<div class='ImageStorage'>";
        echo "<div class='Viewport'>";
        echo "<div class='Collection'>";
        echo "</div>";
        echo "</div>";
        echo "</div>";

    }

}

?>
