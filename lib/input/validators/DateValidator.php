<?php
include_once("lib/input/validators/IInputValidator.php");
include_once("lib/input/InputField.php");

class DateValidator implements IInputValidator
{

	public static function isValidDate($value)
	{
		$pieces = explode("-",$value);

		if (count($pieces)!=3)  {

			return false;
		}

		$ret = false;
		try {
			$year = $pieces[0];
			$month = $pieces[1];
			$day = $pieces[2];
			DateValidator::validate($year, $month, $day);
			$ret = true;
		}
		catch (Exception $e) {

		}
		return $ret;
	}




	protected static function validate($year, $month, $day)
	{

	  if ($year<1)
	  {
		  throw new Exception("Incorrect Date: Year");
	  }

	  if ($month<1 || $month>12)
	  {
		  throw new Exception("Incorrect Date: Month");

	  }

	  if ($day<1 || $day>31)
	  {
		  throw new Exception("Incorrect Date: Day");
	  }

	  $days_in_month = date("t", strtotime($year."-".$month."-"."01"));

	  if ($day>$days_in_month) {
		  throw new Exception("Incorrect Date: Day > $days_in_month");
	  }

	  $u_date = strtotime($year ."-". $month ."-". $day );
	  if ($u_date===false)
	  {
		  throw new Exception("Incorrect Date");
	  }



	}
	public function validateInput(InputField $field)
	{

	
		$pieces = explode("-", $field->getValue());

		if (count($pieces)!=3)  {
		    throw new Exception("Incorrect date (date format)");
		}


		$year = $pieces[0];
		$month = $pieces[1];
		$day = $pieces[2];
		
		

		try {
		  DateValidator::validate($year, $month, $day);
		}
		catch (Exception $e) {
		  if (strlen($year)>0 || strlen($month)>0 || strlen($day)>0 || $field->isRequired()) {
		    throw  $e;
		  }
		  else {
		    if (!$field->isRequired()) {
		      $field->clear();
		    }
		  }
		}


	}

}
?>