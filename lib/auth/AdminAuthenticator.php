<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/AdminUsersBean.php");


class AdminAuthenticator extends Authenticator
{

    public const CONTEXT_NAME = "context_admin";


    public function __construct()
    {
        $this->contextName = AdminAuthenticator::CONTEXT_NAME;
        $this->bean = new AdminUsersBean();

    }

}

?>
