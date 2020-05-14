<?php
include_once("components/Component.php");

class ColorButton extends Component
{

    protected $tagName = "BUTTON";

    protected $text;

    const TYPE_SUBMIT = "submit";
    const TYPE_RESET = "reset";
    const TYPE_BUTTON = "button";

    protected static $default_class = "";

    public static function RenderButton($text = "Button", $href = "")
    {
        $btn = new ColorButton();

        $btn->setText($text);
        //$btn->setValue();
        $btn->setAttribute("onClick", "javascript:document.location.href='$href'");
        $btn->render();
    }

    public static function RenderSubmit($text = "Submit", $name = "submit", $value = "submit")
    {
        $btn = new ColorButton();
        $btn->setType(ColorButton::TYPE_SUBMIT);
        $btn->setText($text);
        $btn->setAttribute("value", $value);
        $btn->setAttribute("name", $name);
        $btn->render();

    }

    public static function SetDefaultClass($css_class)
    {
        self::$default_class = $css_class;
    }

    public function __construct()
    {
        parent::__construct();

        $this->setType(ColorButton::TYPE_BUTTON);
        $this->setClassName(self::$default_class);
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ColorButton.css";
        return $arr;
    }

    public function setType(string $type)
    {
        $this->setAttribute("type", $type);
    }

    public function getType(): string
    {
        return $this->getAttribute("type");
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function setValue(string $value)
    {

        $this->setAttribute("value", $value);

    }

    public function getValue(): ?string
    {
        return $this->getAttribute("value");
    }

    public function renderImpl()
    {
        echo tr($this->text);
    }

}

?>
