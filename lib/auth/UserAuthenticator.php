<?php
include_once("auth/Authenticator.php");
include_once("beans/UsersBean.php");

class UserAuthenticator extends Authenticator
{

    public const CONTEXT_NAME = "UserContext";

    public function __construct()
    {
        parent::__construct(UserAuthenticator::CONTEXT_NAME, new UsersBean());
    }

}

?>