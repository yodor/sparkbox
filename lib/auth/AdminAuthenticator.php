<?php
include_once("lib/auth/Authenticator.php");
include_once ("lib/dbdriver/DBDriver.php");
include_once ("lib/beans/AdminUsersBean.php");


class AdminAuthenticator extends Authenticator
{

		
	public static function checkAuthState($skip_cookie_check=false)
	{
		$ret = parent::checkAuthStateImpl(CONTEXT_ADMIN, $skip_cookie_check);
		if ($ret){
			$adminID = $_SESSION[CONTEXT_ADMIN]["id"];
			self::updateLastSeen($adminID);
		}
		return $ret;

	}
	public static function logout()
	{
		Session::clear(CONTEXT_ADMIN);
		Authenticator::clearAuthState(CONTEXT_ADMIN);
	}
	public static function getAuthContext()
	{
		return CONTEXT_ADMIN;
	}
	public static function authenticate($username, $pass, $rand, $remember_me=false, $check_password_only=false){



		$db = DBDriver::get();

		$bean = new AdminUsersBean();

		$username = $db->escapeString($username);

		$bean->startIterator("WHERE email='$username' LIMIT 1");

		$found=false;
		while ($bean->fetchNext($row))
		{
// 			$stored_user = Authenticator::hmac($row["username"],$rand);
			$stored_user = $row["email"];
			$stored_pass = Authenticator::hmac($row["password"],$rand);


			if (strcmp($stored_user,$username)==0 && strcmp($stored_pass,$pass)==0) {


					$found=true;

					if ((int)$row["suspend"]==0){


						$authstore["id"]=(int)$row["userID"];
						$authstore["fullname"] = $row["fullname"];
						
						$s1="UPDATE admin_users SET counter=counter+1 WHERE userID='".$row["userID"]."'";

						$db->transaction();
						$ret = $db->query($s1);
						$db->commit();

						Authenticator::prepareAuthState(CONTEXT_ADMIN, $authstore);



					}
					else {
						throw new Exception("Your account is temporary suspended.");

					}
					break;
			}
		}
		if ($found===false){

			throw new Exception("Username or password not recognized.");

		}

		return $found;
	}
	public static function updateLastSeen($userID)
	{
		if (!$userID)throw new Exception("userID required");

		$db = DBDriver::get();
		$db->transaction();
		$db->query("UPDATE admin_users set last_active=CURRENT_TIMESTAMP where userID=$userID");
		$db->commit();
	}
}
?>