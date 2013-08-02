<?php
include_once("lib/input/renderers/InputRenderer.php");

class PasswordField extends InputTagRenderer {

  public function __construct()
  {
      parent::__construct();

      $this->setClassName("PasswordField");
      
      $this->setFieldAttribute("type", "password");

  }

}
?>