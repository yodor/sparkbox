<?php
include_once("lib/beans/DBTableBean.php");


class UsersBean extends DBTableBean
{

protected $createString = "
CREATE TABLE `users` (
 `userID` int(10) unsigned NOT NULL auto_increment,
 `username` varchar(255) NOT NULL,
 `password` varchar(32) NOT NULL default '',
 `last_active` datetime NOT NULL default '0000-00-00 00:00:00',
 `counter` int(10) unsigned NOT NULL default '0',
 `date_signup` datetime NOT NULL,
 `suspend` tinyint(1) NOT NULL default '0',
 `is_confirmed` tinyint(1) NOT NULL default '0',
 `confirm_code` varchar(32) NOT NULL,
 PRIMARY KEY  (`userID`),
 UNIQUE KEY `email` (`username`),
 UNIQUE KEY `confirm_code` (`confirm_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
";

	const COMPLETED_NONE = 'None';
	const COMPLETED_LOCATION = 'Location';
	const COMPLETED_INTERESTS = 'Interests';
	const COMPLETED_MAX = UsersBean::COMPLETED_INTERESTS;

	public function __construct()
	{
		parent::__construct("users");
	}

	public function usernameExists($username)
	{
		$db = DBDriver::factory();
		$username = $db->escapeString($username);
		$s=$db->query("SELECT username FROM {$this->table} WHERE username='$username' LIMIT 1");
		return $db->fetchRow($s);
	}
	public function username($userID)
	{
		$user_row =$this->getByID((int)$userID);
		return $user_row["username"];
	}
	public function username2id($username)
	{
		$db = DBDriver::factory();
		$username = $db->escapeString($username);
		$n = $this->startIterator(" WHERE username='$username' LIMIT 1");
		if ($this->fetchNext($urow)){
			return (int)$urow["userID"];
		}
		return -1;
	}


	//email
	public function emailExists($email)
	{
		$db = DBDriver::factory();
		$email = $db->escapeString($email);
		$s = $db->query("SELECT email FROM {$this->table} WHERE email='$email' LIMIT 1");
		return $db->fetchRow($s);
	}
	public function email($userID)
	{
		$user_row = $this->getByID((int)$userID);
		return $user_row["email"];
	}
	public function email2id($email)
	{
		$db = DBDriver::factory();
		$email = $db->escapeString($email);
		$n = $this->startIterator(" WHERE email='$email' LIMIT 1");
		if ($this->fetchNext($urow)){
			return (int)$urow["userID"];
		}
		return -1;
	}


	public static function accountConfirmURL($code)
	{
		return "http://".SITE_DOMAIN.SITE_ROOT."account/confirm.php?code=".$code;
	}
	public static function accountLoginURL($code="")
	{
		return "http://".SITE_DOMAIN.SITE_ROOT."account/login.php";
	}
	public static function accountConfirmPage()
	{
		return SITE_ROOT."account/confirm.php";
	}
}

?>