<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/UsersBean.php");


class UserAuthenticator extends Authenticator
{

	public static function checkAuthState($skip_cookie_check=false)
	{
	    $ret = parent::checkAuthStateImpl(CONTEXT_USER, $skip_cookie_check);
	    if ($ret) {
		$userID = $_SESSION[CONTEXT_USER]["id"];
		self::updateLastSeen($userID);
	    }
	    return $ret;
	}
	
	public static function logout()
	{
		Session::clear(CONTEXT_USER);
		Authenticator::clearAuthState(CONTEXT_USER);
	}
	public static function getAuthContext()
	{
		return CONTEXT_USER;
	}
	public static function fbAuthenticate($oauth_token)
	{

		$userID = -1;

	// 		echo "Expires: $expires<BR>";
			//echo("Hello " . $user_fb->name);

			//echo nl2br(str_replace(' ', ' ', print_r($user_fb, true)));

		$graph_url = "https://graph.facebook.com/me?access_token=$oauth_token";
		$user_fb = json_decode(file_get_contents($graph_url));

		$bean = new UsersBean();
		$email = $user_fb->email;

		$bean->startIterator("WHERE email='$email' LIMIT 1");
		if (!$bean->fetchNext($urow)) throw new Exception("This email is not registered or not confirmed yet.");


		$userID = (int)$urow[$bean->getPrKey()];
		$authstore["id"]=$userID;
		$authstore["fbID"]=(int)$urow["fb_userID"];

		$s1="UPDATE users SET counter=counter+1 , last_active=CURRENT_TIMESTAMP, oauth_token='$oauth_token' WHERE ".$bean->getPrKey()."='$userID'";
		$db = DBDriver::factory();

		$db->transaction();
		$ret = $db->query($s1);
		if (!$ret)throw new Exception($db->getError());
		$db->commit();

		Authenticator::prepareAuthState(CONTEXT_USER, $authstore);

		return $urow;

	}
	public static function authenticate($username, $pass, $rand, $remember_me=false, $check_password_only=false){

		$found = false;

		$db = DBDriver::factory();

		$bean = new UsersBean();

		$username = $db->escapeString($username);

$debug = "";
// $debug = "Username: $username | Password: $pass | Rand: $rand | Remember: $remember_me <HR>";

$user_field = array("username");
if ($bean->haveField("email") && $bean->haveField("username")) {
		$user_field = array("username", "email");
		$bean->startIterator("WHERE email='$username' OR username='$username' LIMIT 1");
}
else if ($bean->haveField("username")) {
		$user_field = array("username");
		$bean->startIterator("WHERE username='$username' LIMIT 1");
}
else if ($bean->haveField("email")) {
		$user_field = array("email");
		$bean->startIterator("WHERE email='$username' LIMIT 1");
}
else {
  throw new Exception("Unable to authenticate. Authentication field missing from table structure.");
}

		while ($bean->fetchNext($row))
		{

			$stored_pass = Authenticator::hmac($row["password"],$rand);



			if ( strcmp($stored_pass,$pass)==0 ) {

				$found = true;

				$is_confirmed = (int)$row["is_confirmed"];
				$is_suspended = (int)$row["suspend"];

				if ($is_confirmed<1) {
					$link = UsersBean::accountConfirmPage();
					throw new Exception("Your account is not confirmed yet. If you want to resend the confirmation code click <a href='$link'>here</a> ");
				}

				if ($is_suspended>0){
					throw new Exception("Your account is temporary suspended.");
				}
				if (!$check_password_only) {
					  $userID = (int)$row[$bean->getPrKey()];
					  $authstore = static::fillAuthStore($row, $bean);

					  $s1="UPDATE users SET counter=counter+1 , last_active=CURRENT_TIMESTAMP WHERE ".$bean->getPrKey()."='$userID'";
					  $db->transaction();
					  $ret = $db->query($s1);
					  if (!$ret)throw new Exception($db->getError());
					  $db->commit();

					  Authenticator::prepareAuthState(CONTEXT_USER, $authstore);
				}
				break;
			}
		}

		if (!$found){

			throw new Exception("Username or password not recognized.". $debug);

		}

		return $found;

	}
	protected static function fillAuthStore($row, IDataBean $bean)
	{
			$authstore = array();
			$authstore["id"]=(int)$row[$bean->getPrKey()];
			return $authstore;
	}
	public static function updateLastSeen($userID) {
		if (!$userID)throw new Exception("userID required");

		global $g_db;
		$g_db->transaction();
		$res = $g_db->query("UPDATE users set last_active=CURRENT_TIMESTAMP where userID=$userID");
if (!$res)throw $g_db->getError();

		$g_db->commit();

	}
}
?>