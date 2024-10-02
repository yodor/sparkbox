<?php
include_once("input/renderers/Input.php");

class ColorButton extends Input
{

    const string TYPE_SUBMIT = "submit";
    const string TYPE_RESET = "reset";
    const string TYPE_BUTTON = "button";

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

    public function __construct(string $type = ColorButton::TYPE_BUTTON, string $name="", string $value = "")
    {

        parent::__construct($type, $name, $value);

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
