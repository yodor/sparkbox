<?php
include_once("lib/input/renderers/InputTagRenderer.php");

class ColorCodeField extends InputTagRenderer
{

  public function __construct()
  {
      parent::__construct();

      $this->setFieldAttribute("type", "color");
      
  }

  


}
?>