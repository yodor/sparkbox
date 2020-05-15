<?php
include_once("components/Component.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/ColorButton.php");

class MessageDialog extends Component implements IPageComponent
{
    const TYPE_PLAIN = 0;
    const TYPE_ERROR = 1;
    const TYPE_INFO = 2;
    const TYPE_QUESTION = 3;

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
        $this->buttonsBar->setClassName("buttons_bar");

        $this->setClassName("PopupPanel");

        $this->setDialogType($this->type);

        $this->initButtons();

    }

    public function getID()
    {
        return $this->id;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/MessageDialog.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/popups/MessageDialog.js";
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

    public function setDialogType($type)
    {
        $this->type = $type;

        $icon_class = "";
        if ($this->type == MessageDialog::TYPE_ERROR) {
            $icon_class = "icon_error";
        }
        else if ($this->type == MessageDialog::TYPE_QUESTION) {
            $icon_class = "icon_question";
        }
        else if ($this->type == MessageDialog::TYPE_INFO) {
            $icon_class = "icon_info";
        }
        else if ($this->type == MessageDialog::TYPE_PLAIN) {
            $icon_class = "";
        }

        $this->icon_class = $icon_class;
    }

    public function startRender()
    {

        parent::startRender();

        if (strlen($this->title) > 0) {
            echo "<div class='caption'>";

            //            if ($this->show_close_button) {
            //                $b = new ColorButton();
            //                $b->setText("X");
            //                $b->setAttribute("action", MessageDialog::BUTTON_ACTION_CLOSE);
            //                $b->render();
            //            }

            echo "<span class='caption_text'>" . tr($this->title) . "</span>";

            echo "<div class=clear></div>";
            echo "</div>";
        }

        echo "<div class='Inner'>";

        if ($this->type === MessageDialog::TYPE_PLAIN) {
            //
        }
        else {
            echo "<div class='message_icon {$this->icon_class}'></div>";
            echo "<div class='message_text'>";
        }
    }

    public function finishRender()
    {
        if ($this->type === MessageDialog::TYPE_PLAIN) {
            //
        }
        else {
            echo "</div>";//message_text
        }

        echo "<div class=clear></div>";

        $this->buttonsBar->render();

        echo "<div class=clear></div>";

        echo "</div>"; //inner

        parent::finishRender();

    }

}

?>
