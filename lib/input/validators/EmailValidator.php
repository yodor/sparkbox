<?php
include_once("lib/input/validators/IInputValidator.php");


class EmailValidator implements IInputValidator
{
	public $domain_check_enabled=true;

	public function __construct($domain_check_enabled=true)
	{
		$this->domain_check_enabled=$domain_check_enabled;
	}

	public function validateInput(InputField $field)
	{
		$ret = "";

		$value = $field->getValue();

		if ( strlen(trim($value)) == 0 ) throw new Exception("Input value");



$regexp="/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";

		if( !preg_match($regexp, $value) )
		{
			throw new Exception("Incorrect email syntax");
		}


		if ($this->domain_check_enabled) {
		  
		  list($username,$domain)=explode('@',$value);

		  if( !$this->checkMX( $domain ) ) throw new Exception("Unrecognized email domain");

		}
		
		
	}
	
	//checkdnsrr and getmxrr are really very slow on the current server use this 
	public function checkMX($domain) 
	{


$ret = checkdnsrr($domain.".", "MX");

return $ret;

// 		  if (!function_exists('checkdnsrr')){ 
// 			  // can we assume it's windows... ?
// 			  // ripped from php.net
// 				exec("nslookup -type=MX $domain", $result);
// 				foreach ($result as $line) {
// 					if(eregi("^$domain",$line)) {
// 						$ret = true;
// 						break;
// 					}
// 				}
// 			  
// 		  } 
// 		  else {
// 			  
// 			  if(checkdnsrr($domain, "MX")){
// 				  $ret = true;
// 			  }
// 			  
// 		  }

//         exec("dig +short MX " . escapeshellarg($domain),$ips);
//         if(!isset($ips[0]) || strlen($ips[0]) < 1) {
//                 return FALSE;
//         }
		
//         return $ret;
	}
// 	public function validateFinal(InputField $field) {}
}
?>