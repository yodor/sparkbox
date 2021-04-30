<?php
include_once("auth/Authenticator.php");
include_once("beans/UsersBean.php");

class UserAuthenticator extends Authenticator
{
    const CONTEXT_NAME = "context_user";

    public function __construct()
    {
        parent::__construct(UserAuthenticator::CONTEXT_NAME, new UsersBean());
    }

}

?>