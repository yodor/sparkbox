<?php
include_once("input/renderers/Input.php");

class Button extends Input
{

    const string TYPE_SUBMIT = "submit";
    const string TYPE_RESET = "reset";
    const string TYPE_BUTTON = "button";

    public static function SubmitButton(string $submit_name) : Button
    {
        $button = Button::TextButton("Submit", "submit");
        $button->setType(self::TYPE_SUBMIT);
        $button->setName($submit_name);
        return $button;
    }

    public static function ActionButton(string $text, string $onClick = "") : Button
    {
        $button = Button::TextButton($text);
        if ($onClick) {
            $button->setAttribute("onClick", $onClick);
        }
        return $button;
    }

    public static function LocationButton(string $text, URL $location) : Button
    {
        return Button::ActionButton($text, "javascript:document.location.href='$location'");
    }

    public static function TextButton(string $text, string $action_value = "") : Button
    {
        $button = new Button(Button::TYPE_BUTTON);
        $button->setContents($text);
        if ($action_value) {
            $button->setAttribute("action", $action_value);
        }
        return $button;
    }

    public function __construct(string $type = Button::TYPE_BUTTON, string $name="", string $value = "")
    {

        parent::__construct($type, $name, $value);
        $this->setComponentClass("ColorButton");

        $this->setTagName("BUTTON");
        $this->setClosingTagRequired(true);

        $this->translation_enabled = TRUE;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ColorButton.css";
        return $arr;
    }

}

?>
