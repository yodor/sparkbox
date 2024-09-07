<?php
include_once("components/Component.php");

class ColorButton extends Component
{

    const string TYPE_SUBMIT = "submit";
    const string TYPE_RESET = "reset";
    const string TYPE_BUTTON = "button";

    protected static string $default_class = "";

    public static function RenderButton($text = "Button", $href = "", $action = "")
    {
        $btn = new ColorButton();

        $btn->setContents($text);

        if ($action) {
            $btn->setAttribute("action", $action);
        }

        if ($href) {
            $btn->setAttribute("onClick", "javascript:document.location.href='$href'");
        }
        $btn->render();
    }

    public static function RenderSubmit($text = "Submit", $name = "submit", $value = "submit")
    {
        $btn = new ColorButton();
        $btn->setType(ColorButton::TYPE_SUBMIT);
        $btn->setContents($text);
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
        parent::__construct(false);

        $this->tagName = "BUTTON";

        $this->setType(ColorButton::TYPE_BUTTON);
        $this->setClassName(self::$default_class);

        $this->translation_enabled = TRUE;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ColorButton.css";
        return $arr;
    }

    /**
     * Set value of HTML attribute 'type'
     * @param string $type Value for attribute 'type'
     * @return void
     */
    public function setType(string $type)
    {
        $this->setAttribute("type", $type);
    }

    /**
     * Get value of HTML attribute 'type'
     * @return string
     */
    public function getType(): string
    {
        return $this->getAttribute("type");
    }

    /**
     * Set value of HTML attribute 'value' and 'aria-label' to '$value'
     * @param string $value Value for attribute 'value'
     * @return void
     */
    public function setValue(string $value) : void
    {
        $this->setAttribute("value", $value);
        $this->setAttribute("aria-label", $value);
    }

    /**
     * Get value of HTML attribute 'value'
     * @return string
     */
    public function getValue(): string
    {
        return $this->getAttribute("value");
    }

}

?>
