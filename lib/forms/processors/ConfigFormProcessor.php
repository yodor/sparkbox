<?php
include_once("lib/forms/processors/FormProcessor.php");

class ConfigFormProcessor extends FormProcessor {

  protected function processImpl(InputForm $form)
  {
	  if (!$form->haveErrors()) {
		 
		  global $config;
		  
		  foreach($form->getFields() as $field_name=> $field) {
			  $field_value = $field->getValue();
			  $config->setValue($field_name, $field_value); 
		  }
		  $this->setMessage("Processed OK");
		  

	  }
	  else {
		  $this->setMessage("Form has field(s) with error.");
		  
	  }
  }

}

?>