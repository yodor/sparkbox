<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

class TimeValidator implements IInputValidator
{
	
	public static function isValidTime($value)
	{
		$pieces = explode(":",$value);

		if (count($pieces)!=2)  {
			
			return false;
		}

		$ret = false;
		try {
			$hour = $pieces[0];
			$minute = $pieces[1];
			
			TimeValidator::validate($hour, $minute);
			$ret = true;
		}
		catch (Exception $e) {

		}
		return $ret;
	}

	protected static function validate($hour, $minute)
	{
	  
	  if ($hour<0 || $hour>23) 
	  {
		  throw new Exception("Incorrect time: hour");
	  }

	  if ($minute<0 || $minute>59) 
	  {
		  throw new Exception("Incorrect time: minute");
		  
	  }

	  

	}
	public function validateInput(InputField $field)
	{
		
		$pieces = explode(":", $field->getValue());

		if (count($pieces)!=2)  {
			throw new Exception("Incorrect time");
			
		}


		$hour = $pieces[0];
		$minute = $pieces[1];
		


		TimeValidator::validate($hour, $minute);
		
		

	}

}
?>