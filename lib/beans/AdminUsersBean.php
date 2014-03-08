<?php
include_once("lib/beans/DBTableBean.php");

class AdminUsersBean extends DBTableBean
{

protected $createString = "CREATE TABLE `admin_users` (
 `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `email` varchar(255) CHARACTER SET ascii NOT NULL,
 `password` varchar(32) CHARACTER SET ascii NOT NULL,
 `context` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
 `level` int(11) NOT NULL DEFAULT '0',
 `access_level` enum('Limited Access','Full Access') NOT NULL DEFAULT 'Limited Access',
 `suspend` tinyint(1) DEFAULT '0',
 `last_active` datetime DEFAULT NULL,
 `counter` bigint(20) unsigned DEFAULT '0',
 `fullname` text NOT NULL,
 `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `adminID` int(11) unsigned DEFAULT NULL,
 PRIMARY KEY (`userID`),
 UNIQUE KEY `username` (`email`),
 KEY `parentID` (`adminID`),
 CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `admin_users` (`userID`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

	public function __construct($dbdriver=NULL)
	{
		parent::__construct("admin_users", $dbdriver);
	}
	
	public function deleteID($id, $db=false) 
	{
	    if ($id==1) throw new Exception("Root admin user is protected");
	    return parent::deleteID($id, $db);
	}
	
	public function updateRecord($id, &$row, &$db=false)
	{
		if ($id == 1 && (isset($row["suspend"]) && $row["suspend"]>0)) throw new Exception("Root admin user is protected");
		return parent::updateRecord($id, $row, $db);

	}
	
	//email
	public function emailExists($email)
	{
	    return $this->findFieldValue("email", $email);
	}
	
	public function email($userID)
	{
	    return $this->fieldValue($userID, "email");
	}
	
	public function email2id($email)
	{
	
	    $row = $this->findFieldValue("email", $email);
	    if (!$row) return -1;
	    return $row[$this->getPrKey()];
		
	}

}

?>
