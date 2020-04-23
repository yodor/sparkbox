<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/AdminUsersBean.php");


class AdminAuthenticator extends Authenticator
{

    const DATA_EMAIL = "email";
    const DATA_FULLNAME = "fullname";

    const DEFAULT_CONTEXT_NAME = "context_admin";


    public function __construct(string $contextName = AdminAuthenticator::DEFAULT_CONTEXT_NAME)
    {
        parent::__construct($contextName);
        $this->setLoginURL(SITE_ROOT . "admin/login.php");
    }

    /**
     * @param string $username
     * @param string $pass
     * @param $rand
     * @param bool $remember_me
     * @param bool $check_password_only
     * @throws Exception
     */
    public function authenticate(string $username, string $pass, $rand, bool $remember_me = false, bool $check_password_only = false)
    {

        $db = DBDriver::Get();

        $bean = new AdminUsersBean();

        $username = $db->escapeString($username);

        $bean->startIterator("WHERE email='$username' LIMIT 1");

        $found = false;
        while ($bean->fetchNext($row)) {

            $stored_user = $row["email"];
            $stored_pass = Authenticator::HMAC($row["password"], $rand);

            if (strcmp($stored_user, $username) == 0 && strcmp($stored_pass, $pass) == 0) {

                $found = true;

                if ((int)$row["suspend"] != 0) {
                    throw new Exception("Your account is temporary suspended.");
                }

                $userID = (int)$row[$bean->key()];

                //context data
                $data = array();
                $data[AdminAuthenticator::DATA_EMAIL] = $row[AdminAuthenticator::DATA_EMAIL];

                if (isset($row[AdminAuthenticator::DATA_FULLNAME])) {
                    $data[AdminAuthenticator::DATA_FULLNAME] = $row[AdminAuthenticator::DATA_FULLNAME];
                }

                $this->store($userID, $data);

                $this->updateLastSeen($userID);

                break;
            }
        }

        if (!$found) {

            throw new Exception("Username or password not recognized.");

        }

    }

    public function updateLastSeen($userID)
    {
        if (!$userID) throw new Exception("userID required");

        $db = DBDriver::Get();
        $db->transaction();
        $db->query("UPDATE admin_users SET counter=counter+1, last_active=CURRENT_TIMESTAMP WHERE userID=$userID");
        $db->commit();
    }
}

?>
