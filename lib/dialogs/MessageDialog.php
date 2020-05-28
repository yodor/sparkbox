<?php
include_once("components/Component.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/ColorButton.php");

class MessageDialog extends Component implements IPageComponent
{
    const TYPE_PLAIN = "";
    const TYPE_ERROR = "Error";
    const TYPE_INFO = "Info";
    const TYPE_QUESTION = "Question";

    const BUTTON_ACTION_CONFIRM = "confirm";
    const BUTTON_ACTION_CANCEL = "cancel";
    const BUTTON_ACTION_CLOSE = "close";

    protected $type = MessageDialog::TYPE_INFO;

    protected $title = "";
    protected $id = "";
    protected $icon_class = "";

    protected $buttonsBar;

    public $show_close_button = FALSE;

    public function __construct($title = "Message", $id = "message_dialog")
    {
        parent::__construct();

        $this->title = $title;
        $this->id = $id;

        $this->setAttribute("id", $id);

        $this->setAttribute("name", $id);

        $this->buttonsBar = new Container();
        $this->buttonsBar->setClassName("Buttons");

        $this->setClassName("PopupPanel");
        $this->addClassName("resizable");

        $this->setDialogType($this->type);

        $this->initButtons();

    }

    public function getID()
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
        $btn_ok = new ColorButton();
        $btn_ok->setContents("OK");
        $btn_ok->setAttribute("action", MessageDialog::BUTTON_ACTION_CONFIRM);
        $btn_ok->setAttribute("default_action", 1);
        $this->buttonsBar->append($btn_ok);
    }

    public function getButtons(): Container
    {
        return $this->buttonsBar;
    }

    public function setDialogType(string $type)
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
