<?php
include_once("lib/auth/Authenticator.php");
include_once("lib/dbdriver/DBDriver.php");
include_once("lib/beans/UsersBean.php");


class UserAuthenticator extends Authenticator
{

    const DEFAULT_CONTEXT_NAME = "context_user";

    const DATA_EMAIL = "email";
    const DATA_FULLNAME = "fullname";

    public function __construct(string $contextName = UserAuthenticator::DEFAULT_CONTEXT_NAME, string $loginURL = "")
    {
        parent::__construct($contextName, $loginURL);
    }


    public function fbAuthenticate($oauth_token)
    {

        $userID = -1;

        // 		echo "Expires: $expires<BR>";
        //echo("Hello " . $user_fb->name);

        //echo nl2br(str_replace(' ', ' ', print_r($user_fb, true)));

        $graph_url = "https://graph.facebook.com/me?access_token=$oauth_token";
        $user_fb = json_decode(file_get_contents($graph_url));

        $bean = new UsersBean();
        $email = $user_fb->email;

        $bean->startIterator("WHERE email='$email' LIMIT 1");
        if (!$bean->fetchNext($urow)) throw new Exception("This email is not registered or not confirmed yet.");


        $userID = (int)$urow[$bean->key()];
        $authstore["id"] = $userID;
        $authstore["fbID"] = (int)$urow["fb_userID"];

        $s1 = "UPDATE users SET counter=counter+1 , last_active=CURRENT_TIMESTAMP, oauth_token='$oauth_token' WHERE " . $bean->key() . "='$userID'";
        $db = DBDriver::Get();

        $db->transaction();
        $ret = $db->query($s1);
        if (!$ret) throw new Exception($db->getError());
        $db->commit();

        $this->store($userID, $authstore);

        return $urow;

    }

    public function authenticate(string $email, string $pass, $rand, bool $remember_me = false, bool $check_password_only = false)
    {

        $found = false;

        $db = DBDriver::Get();

        $bean = new UsersBean();

        $email = $db->escapeString($email);

        $debug = "";

        try {
            if ($bean->haveField("email")) {

                $bean->startIterator("WHERE email='$email' LIMIT 1");
            }
            else {
                throw new Exception(tr("Unable to authenticate. Authentication field missing from table structure."));
            }

            while ($bean->fetchNext($row)) {

                $stored_pass = Authenticator::HMAC($row["password"], $rand);

                if (strcmp($stored_pass, $pass) == 0) {

                    $found = true;

                    $is_confirmed = (int)$row["is_confirmed"];
                    $is_suspended = (int)$row["suspend"];

                    if ($is_confirmed < 1) {
                        $msg = tr("Your account is not confirmed yet.");
                        if (defined(ACCOUNT_CONFIRM_URL)) {
                            $link = ACCOUNT_CONFIRM_URL;
                            $msg .= "<BR>";
                            $msg .= tr("For more details visit the account confirmation page") . ": ";
                            $msg .= "<a href='$link'>" . tr("here") . "</a>";
                        }
                        throw new Exception($msg);
                    }

                    if ($is_suspended > 0) {
                        throw new Exception(tr("Your account is temporary suspended."));
                    }

                    if (!$check_password_only) {
                        $userID = (int)$row[$bean->key()];

                        $data = array();
                        $data[UserAuthenticator::DATA_EMAIL] = $row[UserAuthenticator::DATA_EMAIL];

                        if (isset($row[UserAuthenticator::DATA_FULLNAME])) {
                            $data[UserAuthenticator::DATA_FULLNAME] = $row[UserAuthenticator::DATA_FULLNAME];
                        }

                        $this->store($userID, $data);

                        $this->updateLastSeen(userID);

                    }
                    break;
                }
            }

            if (!$found) {

                throw new Exception(tr("Email or password not recognized.") . $debug);

            }

        }
        catch (Exception $e) {
            sleep(3);
            throw $e;
        }
        return $found;

    }

    public function updateLastSeen($userID)
    {
        if (!$userID) throw new Exception("userID required");

        $db = DBDriver::Get();
        $db->transaction();
        $res = $db->query("UPDATE users SET counter=counter+1, last_active=CURRENT_TIMESTAMP WHERE userID=$userID");
        if (!$res) throw $db->getError();

        $db->commit();

    }
}

?>
