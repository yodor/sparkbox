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

    public function __construct()
    {
        parent::__construct("MCE Image Browser", "mceImage_browser");

        $this->setDialogType(MessageDialog::TYPE_PLAIN);


        $this->buttons = array();

        $btn_cancel = new ColorButton();
        $btn_cancel->setContents("Close");
        $btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $this->buttons[MessageDialog::BUTTON_ACTION_CANCEL] = $btn_cancel;

        $this->handler = new MCEImageBrowserResponder();


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

        echo tr("Existing Images") . ": ";
        echo "<BR>";

        echo "<div class='ImageStorage'>";
        echo "<div class='Contents'>";
        echo "</div>";
        echo "</div>";

    }

}

?>
