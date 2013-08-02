<?php
class Session {


	public function __construct() 
	{
		 
	  session_start();

	}


	public static function contains($key){
		return isset($_SESSION[$key]);
	}
	
	public static function destroy(){
		session_destroy();

	}
	public static function get($key, $default=false){
		if (isset($_SESSION[$key])){
			return $_SESSION[$key];
		}
		return $default;
	}

	public static function set($key, $val){
		$_SESSION[$key]=$val;
	}

	public static function clear($key){
		if (isset($_SESSION[$key])){
			unset($_SESSION[$key]);
		}
	}

	public static function giveCookie($key, $val, $expire=false)
	{
		if (!$expire)	$expire = time() + 60 * 60 * 24 *  365;

		setCookie($key, $val, $expire, "/", COOKIE_DOMAIN);
	}
	public static function cookie($key, $default=false) {
		if (isset($_COOKIE[$key])){
			return $_COOKIE[$key];
		}
		else return $default;
	}
	public static function haveCookie($key) {
		return isset($_COOKIE[$key]);
	}
}
?>