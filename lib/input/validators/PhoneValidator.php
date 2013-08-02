<?php
include_once("lib/input/InputField.php");
include_once("lib/input/validators/IInputValidator.php");

class PhoneValidator implements IInputValidator
{




  public function validateInput(InputField $field)
  {

		$country_code="";
		$city_code="";
		$phone_code="";


		$pieces = explode("|",$field->getValue());

		if (count($pieces)!=3)  {
			throw new Exception("Incorrect phone");

		}

		$country_code = $pieces[0];
		$city_code = $pieces[1];
		$phone_code = $pieces[2];


// if( )
// 		{
// 			throw new Exception("Incorrect country code");
// 		}
// if( )
// 		{
// 			throw new Exception("Incorrect city code");
// 		}
// if()
// 		{
// 			throw new Exception("Incorrect phone code");
// 		}

		$ret = true;

		$have_entry = (
			strlen($country_code)>0 ||
			strlen($city_code)>0 ||
			strlen($phone_code)>0
		);


		if ($field->isRequired() || $have_entry) {
			if ((int)$country_code<1 || !preg_match( "/^([0-9])+$/", $country_code))
			{
				$err ="Input country code";
				$ret=false;
			}

			if ((int)$city_code<1 || !preg_match( "/^([0-9])+$/", $city_code))
			{
				$err ="Input city code";
				$ret=false;
			}

			if ((int)$phone_code<1 || !preg_match( "/^([0-9])+$/", $phone_code))
			{
				$err ="Input phone number";
				$ret=false;
			}
		}
		if (!$ret){
			throw new Exception($err);
		}

  }

}
?>