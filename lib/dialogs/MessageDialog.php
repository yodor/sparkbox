<?php
include_once("components/Component.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/Button.php");
include_once("components/renderers/ITemplate.php");

class MessageDialog extends Container implements IPageComponent
{
    const string TYPE_PLAIN = "";
    const string TYPE_ERROR = "Error";
    const string TYPE_INFO = "Info";
    const string TYPE_QUESTION = "Question";

    const string BUTTON_ACTION_CONFIRM = "confirm";
    const string BUTTON_ACTION_CANCEL = "cancel";
    const string BUTTON_ACTION_CLOSE = "close";

    protected string $type = MessageDialog::TYPE_INFO;

    protected string $title = "";
    protected string $id = "";
    protected string $icon_class = "";

    protected Container $buttonsBar;

    public $show_close_button = FALSE;

    public function __construct(string $title = "Message", string $id = "message_dialog")
    {
        //make component created event happy
        $this->title = $title;
        $this->id = $id;

        parent::__construct(false);
        $this->addClassName("MessageDialog");

        $this->setAttribute("id", $id);

        $this->setAttribute("name", $id);

        $this->buttonsBar = new Container(false);
        $this->buttonsBar->setClassName("Buttons");

        $this->addClassName("PopupPanel");
        $this->addClassName("resizable");

        $this->setDialogType($this->type);

        $this->initButtons();

    }

    public function getID() : string
    {
        return $this->id;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/MessageDialog.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/dialogs/MessageDialog.js";
        $arr[] = SPARK_LOCAL . "/js/dialogs/json/JSONDialog.js";
        return $arr;
    }

    protected function initButtons()
    {
        $btn_ok = new Button();
        $btn_ok->setContents("OK");
        $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);
        $btn_ok->setAttribute("default_action", 1);
        $this->buttonsBar->items()->append($btn_ok);
    }

    public function getButtons(): Container
    {
        return $this->buttonsBar;
    }

    public function setDialogType(string $type) : void
    {
        $this->type = $type;
        $this->setAttribute("type", $type);
    }

    public function startRender()
    {

        parent::startRender();

        echo "<div class='Inner'>";

        echo "<div class='Header'>";

        if (strlen($this->title) > 0) {
            echo "<div class='Caption'>";

            echo "<span class='Title'>" . tr($this->title) . "</span>";

            echo "</div>";
        }

        echo "</div>";

        echo "<div class='Center'>";

        echo "<div class='Contents'>";

        echo "<div class='Icon'></div>";

        echo "<div class='Text'>";
    }

    public function finishRender()
    {
        echo "</div>";

        echo "</div>";//Contents

        echo "</div>"; //center

        echo "<div class='Footer'>";
        $this->buttonsBar->render();
        echo "</div>";

        echo "</div>"; //Inner

        ?>
        <div class='resizer top-left'></div>
        <div class='resizer top-right'></div>
        <div class='resizer bottom-left'></div>
        <div class='resizer bottom-right'></div>
        <?php

        parent::finishRender();

    }

}

?>
