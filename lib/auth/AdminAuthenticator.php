<?php
include_once("auth/Authenticator.php");
include_once("beans/AdminUsersBean.php");
include_once("beans/AdminAccessBean.php");

class AdminAuthenticator extends Authenticator
{

    public const string ACCESS_LIMITED = "Limited Access";
    public const string ACCESS_FULL = "Full Access";
    public const string SESSION_DATA_ACCESS_LEVEL = "access_level";
    public const string SESSION_DATA_ENABLED_ROLES = "enabled_roles";

    public const string CONTEXT_NAME = "AdminContext";

    public function __construct()
    {
        parent::__construct(self::CONTEXT_NAME, new AdminUsersBean());
    }

    protected function fillSessionData(array $row, int $userID) : void
    {
        parent::fillSessionData($row, $userID);
        $access_level = $row[AdminAuthenticator::SESSION_DATA_ACCESS_LEVEL];

        $this->session->set(AdminAuthenticator::SESSION_DATA_ACCESS_LEVEL, $access_level);

        if (strcmp($access_level, AdminAuthenticator::ACCESS_LIMITED) == 0) {
            $admin_access = new AdminAccessBean();
            $qry = $admin_access->queryField("userID", $userID, 0, "role");
            $numResults = $qry->exec();
            $roles = array();
            while ($row = $qry->next()) {
                $roles[]=$row["role"];
            }
            $this->session->set(AdminAuthenticator::SESSION_DATA_ENABLED_ROLES, $roles);
        }
    }
}