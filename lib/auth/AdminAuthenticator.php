<?php
include_once("auth/Authenticator.php");
include_once("beans/AdminUsersBean.php");

class AdminAuthenticator extends Authenticator
{

    public const CONTEXT_NAME = "AdminContext";

    public function __construct()
    {
        parent::__construct(self::CONTEXT_NAME, new AdminUsersBean());
    }

}

?>