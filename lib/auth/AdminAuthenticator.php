<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/AdminUsersBean.php");


class AdminAuthenticator extends Authenticator
{

    const CONTEXT_NAME = "context_admin";


    public function __construct()
    {
        $this->contextName = CONTEXT_NAME;
        $this->bean = new AdminUsersBean();
        $this->setLoginURL(SITE_ROOT . "admin/login.php");
    }

}

?>
