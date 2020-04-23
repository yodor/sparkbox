<?php
include_once("lib/auth/AuthContext.php");
include_once("lib/auth/AuthData.php");

/**
 * Abstract class for doing authentication
 * Reimplement to get context specific authenticator
 */
abstract class Authenticator
{

    protected $contextName = "";
    protected $loginURL = "";

    protected $bean = null;

    public function __construct()
    {
    }

    public static function HMAC($key, $data, $hash = 'md5', $blocksize = 64)
    {
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hash($key));
        }
        $key = str_pad($key, $blocksize, chr(0));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        return $hash(($key ^ $opad) . pack('H*', $hash(($key ^ $ipad) . $data)));
    }

    public static function RandomToken(int $length = 32)
    {
        $md5_size = 32;

        if ($length > $md5_size) {
            $length = $md5_size;
        }

        // Generate string with random data (max length $md5_size)
        $string = md5(microtime(true) . "|" . rand());

        // Position Limiting
        $start_point = 32 - $length;

        // Take a random starting point in the randomly
        // Generated String, not going any higher then $start_point
        return substr($string, rand(0, $start_point), $length);
    }


    /**
     * Return the context name this class is handling
     * @return string
     */
    public function name()
    {
        return $this->contextName;
    }

    /**
     * Logout. Clears any stored authentication data for a named context - '$name'
     */
    public function logout()
    {
        Session::Clear($this->contextName);
        foreach ($_COOKIE as $key => $val) {
            if (strpos($key, $this->contextName) === 0) {
                setcookie($key, "", 1, "/");
            }
        }
    }

    public function register(array $urow)
    {

        $userID = $this->bean->insert($urow);
        if ($userID < 1) {
            error_log("Authenticator::register() Error: ". $this->bean->getDB()->getError());
            throw new Exception(tr("Error during registering. Please try again later."));
        }

        $data = $this->prepareAuthData($urow);

        return $this->createContext($userID, $data);
    }

    /**
     * Validate authentication of this named context and return the AuthContext object or NULL
     * @param array|null $user_data
     * @return mixed|null
     */
    public function authorize(array $user_data = NULL)
    {

        if (!Session::Contains($this->contextName)) {
            debug(get_class($this) . " Session array does not contain AuthContext with name: " . $this->contextName);
            return NULL;
        }

        $context = unserialize(Session::Get($this->contextName));

        if ($context instanceof AuthContext) {
            debug(get_class($this) . " Validating un-serialized AuthContext using name: ". $this->contextName);

            if ($context->validate($this->contextName) === TRUE) {
                debug(get_class($this) . " Validation success name: ". $this->contextName);
                return $context;
            }
            else {
                debug(get_class($this) . " AuthContext validation failed");
            }
        }
        else {
            debug(get_class($this) . " AuthContext un-serialize failed");
        }

        return NULL;
    }


    /**
     * @param string $username
     * @param string $pass
     * @param $rand
     * @param bool $remember_me
     * @param bool $check_password_only
     * @throws Exception
     */
    public function login(string $username, string $pass, string $rand, bool $remember_me = false, bool $check_password_only = false)
    {

        $db = DBDriver::Get();

        $username = $db->escapeString($username);

        $this->bean->startIterator("WHERE email='$username' LIMIT 1");

        try {
            $row = array();
            if (!$this->bean->fetchNext($row)) throw new Exception("Username or password not recognized");

            $userID = $row[$this->bean->key()];

            $stored_user = $row["email"];
            $stored_pass = Authenticator::HMAC($row["password"], $rand);

            if (strcmp($stored_user, $username) !== 0 || strcmp($stored_pass, $pass) !== 0) {
                throw new Exception(tr("Username or password not recognized"));
            }

            if (isset($row["confirmed"])) {
                $is_confirmed = (int)$row["is_confirmed"];
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
            }
            if (isset($row["suspended"])) {
                $is_suspended = (int)$row["suspended"];
                if ($is_suspended) throw new Exception(tr("Your account is temporary suspended."));
            }


            $data = $this->prepareAuthData($row);

            $this->createContext($userID, $data);

            $this->updateLastSeen($userID);

        }
        catch (Exception $e) {
            sleep(3);
            throw $e;
        }
    }

    protected function updateLastSeen(int $userID)
    {

        $row = array();
        $row["counter"] = "counter+1";
        $row["last_active"] = "CURRENT_TIMESTAMP";

        $this->bean->updateRecord($userID, $row);

    }

    /**
     * Called from Login or after registration routines are complete to set the logged in state of the context
     * Store authentication data in session and cookie for a named context '$name'
     * @param int $id
     * @param AuthData $data
     * @return AuthContext
     */
    protected function createContext(int $id, AuthData $data)
    {
        debug(get_class($this) . " Creating new AuthContext ... ");

        session_regenerate_id(true);

        debug(get_class($this) . " SessionID regenerated ...");

        $context = new AuthContext($id,  $data);

        $context->store($this->contextName);

        return $context;
    }

    protected function prepareAuthData(array $row)
    {
        $data = new AuthData($this->contextName);
        if (isset($row[AuthData::DATA_EMAIL])) {
            $data->set(AuthData::DATA_EMAIL, $row[AuthData::DATA_EMAIL]);
        }
        if (isset($row[AuthData::DATA_FULLNAME])) {
            $data->set(AuthData::DATA_FULLNAME , $row[AuthData::DATA_FULLNAME]);
        }
        return $data;
    }

    public function fbAuthenticate($oauth_token)
    {

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

        $this->createContext($userID, $authstore);

        $this->updateLastSeen($userID);

        return $urow;

    }
}

?>
