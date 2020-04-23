<?php
include_once("lib/panels/MessageDialog.php");
include_once("lib/handlers/MCEImageBrowserAjaxHandler.php");

class MCEImageBrowserDialog extends MessageDialog
{

    protected $handler = NULL;
    protected $dimension_dialog = NULL;

    public function __construct()
    {
        parent::__construct("MCE Image Browser", "mceImage_browser");

        $this->setDialogType(MessageDialog::TYPE_PLAIN);

        $this->show_close_button = false;

        $this->buttons = array();

        $btn_cancel = StyledButton::DefaultButton();
        $btn_cancel->setButtonType(StyledButton::TYPE_BUTTON);
        $btn_cancel->setText("Close");
        $btn_cancel->setAttribute("action", MessageDialog::BUTTON_ACTION_CANCEL);
        $this->buttons[MessageDialog::BUTTON_ACTION_CANCEL] = $btn_cancel;


        $this->handler = new MCEImageBrowserAjaxHandler();
        RequestController::addAjaxHandler($this->handler);

        $this->image_input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "mceImage", "Upload Image", 1);
        $this->image_input->getArrayRenderer()->assignUploadHandler($this->handler);


    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/popups/MCEImageBrowserDialog.js";
        return $arr;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function setHandler(UploadControlAjaxHandler $handler)
    {
        $this->handler = $handler;
    }


    //final method
    public function renderImpl()
    {

        echo "<form method='post' enctype='multipart/form-data'>";
        $icmp = new InputComponent();

        $icmp->setField($this->image_input);
        $icmp->render();


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
