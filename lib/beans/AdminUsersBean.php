<?php
include_once("beans/DBTableBean.php");

class AdminUsersBean extends DBTableBean
{

    protected string $createString = "CREATE TABLE `admin_users` (
 `userID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `email` varchar(255) CHARACTER SET ascii NOT NULL,
 `password` varchar(32) CHARACTER SET ascii NOT NULL,
 `context` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
 `level` int(11) NOT NULL DEFAULT '0',
 `access_level` enum('Limited Access','Full Access') NOT NULL DEFAULT 'Limited Access',
 `suspend` tinyint(1) DEFAULT '0',
 `last_active` datetime DEFAULT NULL,
 `counter` bigint(20) unsigned DEFAULT '0',
 `fullname` varchar(255) NOT NULL DEFAULT '',
 `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `adminID` int(11) unsigned DEFAULT NULL,
 PRIMARY KEY (`userID`),
 UNIQUE KEY `username` (`email`),
 KEY `parentID` (`adminID`),
 CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `admin_users` (`userID`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    public function __construct()
    {
        parent::__construct("admin_users");
    }

    public function delete(int $id, ?DBDriver $db = NULL) : int
    {
        if ($id == 1) throw new Exception("Root admin user is protected");
        return parent::delete($id, $db);
    }

    public function update(int $id, array $row, ?DBDriver $db = NULL) : int
    {
        if ($id == 1 && (isset($row["suspend"]) && $row["suspend"] > 0)) throw new Exception("Root admin user is protected");
        return parent::update($id, $row, $db);

    }

    //email
    public function emailExists(string $email)
    {
        return $this->getResult("email", $email);

    }

    public function email(int $userID)
    {
        return $this->getValue($userID, "email");
    }

    public function email2id(string $email)
    {

        $row = $this->getResult("email", $email);
        if (!$row) return -1;
        return $row[$this->key()];

    }

}

?>
