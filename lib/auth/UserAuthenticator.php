<?php
include_once("auth/Authenticator.php");
include_once("beans/UsersBean.php");

class UserAuthenticator extends Authenticator
{

    public function __construct()
    {
        parent::__construct(new UsersBean());
    }

}

?>