<?php
include_once("lib/input/processors/CompoundInputProcessor.php");

class PhoneInputProcessor extends CompoundInputProcessor
{

  public function __construct()
  {
      $this->compound_names = array("country", "city", "phone");
      $this->concat_char="|";
      parent::__construct();
      
  }

}

?>