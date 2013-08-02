<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

class URLValidator implements IInputValidator
{


	public function validateInput(InputField $field)
	{
		$val = $field->getValue();

		$proto_http = substr($val,0,7);
		$proto_https = substr($val,0,8);

		if ( strcasecmp($proto_http,"http://") == 0 || strcasecmp($proto_https,"https://")==0 ) 
		{
			
		}
		else {
			throw new Exception("HTTP or HTTPS :// required");
		}
		
	}

}
?>