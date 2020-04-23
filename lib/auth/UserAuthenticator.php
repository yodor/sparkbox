<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/UsersBean.php");


class UserAuthenticator extends Authenticator
{

    const CONTEXT_NAME = "context_user";

    public function __construct()
    {
        parent::__construct();

        $this->contextName = UserAuthenticator::CONTEXT_NAME;
        $this->bean = new UsersBean();

    }




}

?>
