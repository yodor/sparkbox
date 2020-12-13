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
    public function emailExists(string $email)
    {
        return $this->getResult("email", $email);
    }

    /**
     * @param int $userID The userID to get the email field form
     * @return string email address field matching this userID
     * @throws Exception
     */
    public function email(int $userID) : string
    {
        return $this->getValue($userID, "email");
    }

    /**
     * @param string $email The email to look for
     * @return int userID matching the email or -1 of email is not found
     * @throws Exception
     */
    public function email2id(string $email) : int
    {

        $row = $this->getResult("email", $email);
        if (!$row) return -1;
        return $row[$this->key()];

    }

    /**
     * @param $confirm_code string The confirmation code to look for
     * @return int userID matching the confirmation code
     * @throws Exception
     */
    public function confirm2id(string $confirm_code) : int
    {
        $row = $this->getResult("confirm_code", $confirm_code);
        if (!$row) return -1;
        return $row[$this->key()];
    }
}

?>
