<?php
include_once("beans/DBTableBean.php");

class UsersBean extends DBTableBean
{

    protected string $createString = "CREATE TABLE `users` (
 `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `email` varchar(255) NOT NULL,
 `phone` varchar(32) NOT NULL DEFAULT '',
 `password` varchar(32) NOT NULL DEFAULT '',
 `last_active` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `counter` int(11) unsigned NOT NULL DEFAULT '0',
 `fullname` varchar(255) NOT NULL DEFAULT '',
 `date_signup` datetime NOT NULL,
 `suspended` tinyint(1) NOT NULL DEFAULT '0',
 `confirmed` tinyint(1) NOT NULL DEFAULT '0',
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

    /**
     * @param string $email
     * @param string $confirm_code
     * @param DBDriver|null $db
     * @return int The number of rows affected
     * @throws Exception
     */
    public function activate(string $email, string $confirm_code, ?DBDriver $db = NULL) : int
    {
        if (!$email || !$confirm_code) throw new Exception("Unable to activate with empty 'email' or 'confirm code'");

        $userID_email = $this->email2id($email);
        if ($userID_email<1) throw new Exception(tr("Unknown email address. Please register to create a new profile."));

        $userID_code = $this->confirm2id($confirm_code);
        if ($userID_code<1) throw new Exception(tr("Incorrect activation code. Please check the activation code and try again."));

        if ($userID_email != $userID_code) throw new Exception(tr("This activation code does not match the email address."));

        $is_confirmed = (int)$this->getValue($userID_email, "confirmed");
        if ($is_confirmed>0) throw new Exception(tr("This profile is already activated."));


        $email = "'".DBConnections::Open()->escape(strtolower(trim($email)))."'";
        $confirm_code = "'".DBConnections::Open()->escape(strtolower(trim($confirm_code)))."'";


        $code = function (DBDriver $db) use ($email, $confirm_code) {

            $update = new SQLUpdate($this->select);

            $update->set("confirmed", 1);

            $update->where()->add("email", $email);
            $update->where()->add("confirm_code", $confirm_code);

            $db->query($update->getSQL());
        };

        return $this->handleTransaction($code, $db);
    }

}

?>