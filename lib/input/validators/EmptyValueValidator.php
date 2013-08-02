<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

class EmptyValueValidator implements IInputValidator
{
  public function validateInput(InputField $field)
  {

	  $value = $field->getValue();

	  //checkbox and radios receive array of values here
	  if (is_array($value)) {

	      if ($field->isRequired()) {
		      if (count($value)<1) throw new Exception("Input value ");
	      }

	  }
	  else {

	    if ( strlen(trim($value)) == 0 && $field->isRequired()){
		throw new Exception("Input value ");
	    }

	    if ($field->getLinkMode() && strlen(trim($value)) === 0 ) {
		$link_field = $field->getLinkField();
		$fti = $link_field->getRenderer()->getFreetextInput();
		if (strcmp($fti, $link_field->getValue())===0) {
		      throw new Exception("Please specify other");
		}
	    }

	  }


  }

}
?>