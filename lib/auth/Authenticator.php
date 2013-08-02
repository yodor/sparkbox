<?php
abstract class Authenticator
{

    public static $lastID = -1;
    
    public static function hmac($key, $data, $hash = 'md5', $blocksize = 64)
    {
	if (strlen($key)>$blocksize) {
	$key = pack('H*', $hash($key));
	}
	$key  = str_pad($key, $blocksize, chr(0));
	$ipad = str_repeat(chr(0x36), $blocksize);
	$opad = str_repeat(chr(0x5c), $blocksize);
	return $hash(($key^$opad) . pack('H*', $hash(($key^$ipad) . $data)));
    }


    public static function generateRandomAuth($length=32)
    {
	// Generate random 32 charecter string
	$string = md5(time()."|".rand());

	// Position Limiting
	$highest_startpoint = 32-$length;

	// Take a random starting point in the randomly
	// Generated String, not going any higher then $highest_startpoint
	$randomString = substr($string,rand(0,$highest_startpoint),$length);

	return $randomString;
    }

    public static function logout() 
    {
	throw new Exception("Not implemented");
    }


    protected static function checkAuthStateImpl($auth_context, $skip_cookie_check=false)
    {
	self::$lastID = -1;
	
	if (!Session::contains($auth_context)) return false;

	$auth_array = $_SESSION[$auth_context];

	if (!isset($auth_array["auth"]) || !isset($auth_array["id"])) return false;

	$session_auth = $auth_array["auth"];
	$session_id = $auth_array["id"];

	if ($skip_cookie_check) {
	    self::$lastID = $session_id;
	    return true;
	}
	
	//session expired
	if (!isset($_COOKIE[$auth_context."_auth"]) || !isset($_COOKIE[$auth_context."_id"]) ) return false;

	$cookie_auth = $_COOKIE[$auth_context."_auth"];
	$cookie_id = $_COOKIE[$auth_context."_id"];

	if (strcmp($session_auth, $cookie_auth)==0 && strcmp($session_id, $cookie_id)==0 )
	{
	    self::$lastID = $session_id;
	    return true;
	}
	
	return false;
    }
    
    protected static function clearAuthState($context)
    {
	setcookie($context."_id","",1,"/", COOKIE_DOMAIN);
	setcookie($context."_auth","",1,"/", COOKIE_DOMAIN);
    }
    
    public static function prepareAuthState($context, $authstore)
    {
	session_regenerate_id(true);

	$expire = time() + 60 * 60 * 24 *  365; // set expire to two hours // one month = time()+60*60*24*30;

	$auth_token=Authenticator::generateRandomAuth();


	$_SESSION[$context]["auth"]=$auth_token;
	setcookie($context."_auth", $auth_token, $expire, "/", COOKIE_DOMAIN);

	foreach ($authstore as $key=>$val){
	  $_SESSION[$context][$key]=$val;

	  setcookie($context."_".$key, $val, $expire, "/", COOKIE_DOMAIN);
	}
	// do not redirect below
    }
    
    public  static function authenticate($username, $pass, $rand, $remember_me=false, $check_password_only=false)
    {
	    throw new Exception("Not implemented");
    }
    public  static function updateLastSeen($userID)
    {
	    throw new Exception("Not implemented");
    }
    public  static function checkAuthState($skip_cookie_check=false)
    {
	    throw new Exception("Not implemented");
    }

    public  static function getAuthContext()
    {
	throw new Exception("Not implemented");
    }
}
?>