<?php
include_once("beans/DBTableBean.php");


class UsersBean extends DBTableBean
{

    protected $createString = "CREATE TABLE `users` (
 `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `email` varchar(255) NOT NULL,
 `phone` varchar(32) NOT NULL DEFAULT '',
 `password` varchar(32) NOT NULL DEFAULT '',
 `last_active` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `counter` int(11) unsigned NOT NULL DEFAULT '0',
 `fullname` varchar(255) NOT NULL DEFAULT '',
 `date_signup` datetime NOT NULL,
 `suspend` tinyint(1) NOT NULL DEFAULT '0',
 `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
 `confirm_code` varchar(32) DEFAULT '',
 PRIMARY KEY (`userID`),
 UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";


    public function __construct()
    {
        parent::__construct("users");
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
        return $row[$this->key()];

    }

}

?>
