<?php
include_once("beans/ResetLinksBean.php");

/**
 * The password reset links of the administrators (the admin 'forgot password') - ResetLinksBean
 */
class AdminPasswordResetsBean extends ResetLinksBean
{
    protected string $createString = "
CREATE TABLE `admin_password_resets` (
 `prID` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `userID` int(11) unsigned NOT NULL,
 `token_hash` char(64) NOT NULL,
 `created` int(11) unsigned NOT NULL,
 `expires` int(11) unsigned NOT NULL,
 PRIMARY KEY (`prID`),
 UNIQUE KEY `token_hash` (`token_hash`),
 UNIQUE KEY `userID` (`userID`),
 CONSTRAINT `admin_password_resets_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `admin_users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
";

    public function __construct()
    {
        parent::__construct("admin_password_resets");
    }
}
