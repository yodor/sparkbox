<?php
include_once("components/Component.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/Button.php");
include_once("components/renderers/ITemplate.php");

class MessageDialog extends Container implements IPageComponent, ITemplate
{
    //https://www.svgrepo.com/collection/wolf-kit-rounded-line-icons/
    const string TYPE_PLAIN = "Plain";
    const string TYPE_ERROR = "Error";
    const string TYPE_INFO = "Info";
    const string TYPE_QUESTION = "Question";

    const string BUTTON_ACTION_CONFIRM = "confirm";
    const string BUTTON_ACTION_CANCEL = "cancel";
    const string BUTTON_ACTION_CLOSE = "close";

    protected string $type = MessageDialog::TYPE_INFO;

    protected Component $title;

    protected Container $buttonsBar;

    protected Container $content;

    protected Container $text;

    protected bool $singleInstance = false;
    protected bool $modal = true;

    public function __construct()
    {

        parent::__construct(false);
        $this->addClassName("Dialog");

        $inner = new Container("false");
        $inner->setComponentClass("Inner");
        $this->items()->append($inner);

        $header = new Container(false);
        $header->setComponentClass("Header");
        $inner->items()->append($header);

        $title = new Component(false);
        $title->setComponentClass("Title");
        $title->setContents("Message");
        $header->items()->append($title);
        $this->title = $title;

        $content = new Container(false);
        $content->setComponentClass("Content");
        $inner->items()->append($content);
        $this->content = $content;

        $icon = new Container(false);
        $icon->setComponentClass("Icon");
        $content->items()->append($icon);

        $text = new Container(false);
        $text->setComponentClass("Text");
        $content->items()->append($text);
        $this->text = $text;

        $footer = new Container(false);
        $footer->setComponentClass("Footer");

        $this->buttonsBar = new Container(false);
        $this->buttonsBar->setComponentClass("Buttons");

        $footer->items()->append($this->buttonsBar);

        $inner->items()->append($footer);

        $this->initButtons();

    }

    public function setText(string $text) : void
    {
        $this->text->setContents($text);
    }
    public function getText() : string
    {
        return $this->text->getContents();
    }

    public function setTitle(string $text) : void
    {
        $this->title->setContents($text);
    }
    public function getTitle() : string
    {
        return $this->title->getContents();
    }

    public function setSingleInstance(bool $mode) : void
    {
        $this->singleInstance = $mode;
    }
    public function isSingleInstance() : bool
    {
        return $this->singleInstance;
    }

    public function setModal(bool $mode) : void
    {
        $this->modal = $mode;
    }
    public function isModal() : bool
    {
        return $this->modal;
    }

    public function templateID() : string
    {
        return get_class($this);
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/MessageDialog.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/js/dialogs/MessageDialog.js";
        return $arr;
    }

    protected function initButtons() : void
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

    public function setType(string $type) : void
    {
        $this->type = $type;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        $this->setAttribute("type", $this->type);

        if ($this->singleInstance) {
            $this->setAttribute("single");
        }
        else {
            $this->removeAttribute("single");
        }

        if ($this->modal) {
            $this->setAttribute("modal");
        }
        else {
            $this->removeAttribute("modal");
        }

    }


}