<?php
include_once("lib/input/renderers/InputRenderer.php");
require_once("lib/securimage/securimage.php");

class CaptchaInputRenderer extends InputRenderer {

  protected $options = null;
  
  public function __construct($options=null)
  {
        parent::__construct();
        
        if (is_array($options)) {
            $this->options = $options;
        }
        else {
            
            $options = array();
            
            $options["disable_flash_fallback"] = true; // allow flash fall      
            $options["show_text_input"] = true;
            $options["refresh_alt_text"] = "Обнови";
            $options["refresh_title_text"] = "Обнови";
            $options["input_text"] = "";
            $options["show_audio_button"] = false;
            $options["captcha_type"] = Securimage::SI_CAPTCHA_MATHEMATIC;
            
            $this->options = $options;
        }
  }

  protected function renderImpl()
  {
	$field_value = $this->field->getValue();
	$field_name = $this->field->getName();

	
        $this->options["input_name"] = $field_name;
	
        echo Securimage::getCaptchaHtml($this->options);
        

	
  }


}
?>
