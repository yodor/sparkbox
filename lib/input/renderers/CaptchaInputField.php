<?php
include_once("input/renderers/InputField.php");
require_once("securimage/securimage.php");

class CaptchaInputField extends InputField
{

    protected $options = NULL;

    public function __construct(DataInput $input, $options = NULL)
    {
        parent::__construct($input);

        if (is_array($options)) {
            $this->options = $options;
        }
        else {

            $options = array();

            $options["disable_flash_fallback"] = TRUE; // allow flash fall
            $options["show_text_input"] = TRUE;
            $options["refresh_alt_text"] = "Обнови";
            $options["refresh_title_text"] = "Обнови";
            $options["input_text"] = "";
            $options["show_audio_button"] = FALSE;
            $options["captcha_type"] = Securimage::SI_CAPTCHA_MATHEMATIC;

            $options['securimage_path'] = "/securimage/";

            $this->options = $options;
        }
    }

    protected function renderImpl()
    {
        $field_value = $this->input->getValue();
        $field_name = $this->input->getName();

        $this->options["input_name"] = $field_name;

        echo Securimage::getCaptchaHtml($this->options);

    }

}

?>