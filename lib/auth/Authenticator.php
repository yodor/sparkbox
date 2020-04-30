<?php
include_once("lib/auth/AuthContext.php");
include_once("lib/auth/AuthToken.php");
include_once("lib/utils/SessionData.php");

/**
 * Abstract class for doing authentication
 * Reimplement to get context specific authenticator
 */
abstract class Authenticator
{
    /**
     * @var IDataBean
     */
    protected $bean = null;

    protected $session = null;

    public function __construct(string $contextName, IDataBean $bean)
    {
        $this->bean = $bean;
        $this->session = new SessionData($contextName);
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

    /**
     * @param int $length
     * @return false|string
     */
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
        return $this->session->name();
    }

    /**
     * Logout. Clears any stored authentication data for a named context - '$name'
     */
    public function logout()
    {
        $this->session->clear();
        foreach ($_COOKIE as $key => $val) {
            if (strpos($key, $this->session->name()) === 0) {
                setcookie($key, "", 1, "/");
            }
        }
    }

    /**
     * @param array $urow
     * @return AuthContext
     * @throws Exception
     */
    public function register(array $urow)
    {

        $userID = $this->bean->insert($urow);
        if ($userID < 1) {
            error_log("Authenticator::register() Error: ". $this->bean->getDB()->getError());
            throw new Exception(tr("Error during registering. Please try again later."));
        }

        $this->fillSessionData($urow);

        $this->createAuthToken($userID);

        return new AuthContext($userID, $this->session);
    }


    /**
     * @param array|null $user_data
     * @return AuthContext|null
     */
    public function authorize(array $user_data = NULL)
    {

        if (!$this->session->contains(SessionData::AUTH_TOKEN)) {
            debug(get_class($this) . " SessionData does not have auth_token set");
            return NULL;
        }

        $auth = $this->session->get(SessionData::AUTH_TOKEN);

        $token = unserialize($auth);

        if ($token instanceof AuthToken) {
            debug(get_class($this) . " AuthToken un-serialized from session");

            if ($token->validateCookies($this->session->name()) === TRUE) {
                debug(get_class($this) . " Cookie validation success");
                return new AuthContext($token->getID(), $this->session);
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

        debug(get_class($this) . " Login process using loginToken: " . $rand);

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


            $this->fillSessionData($row);

            $this->createAuthToken($userID);

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

        $this->bean->update($userID, $row);

    }

    /**
     * Create new AuthToken for user ID $id.
     * Serialize the token to the SessionData of this Authenticator
     * @param int $id
     */
    protected function createAuthToken(int $id)
    {
        debug(get_class($this) . " createAuthToken");

        session_regenerate_id(true);

        debug(get_class($this) . " SessionID regenerated");

        $token = new AuthToken($id);

        $token->storeCookies($this->session->name());
        debug(get_class($this) . " Cookies created");

        $this->session->set(SessionData::AUTH_TOKEN, serialize($token));
        debug(get_class($this) . " AuthToken stored to SessionData");
    }

    protected function fillSessionData(array $row)
    {
        debug(get_class($this) . " fillSessionData");

        if (isset($row[SessionData::EMAIL])) {
            $this->session->set(SessionData::EMAIL, $row[SessionData::EMAIL]);
        }
        if (isset($row[SessionData::FULLNAME])) {
            $this->session->set(SessionData::FULLNAME , $row[SessionData::FULLNAME]);
        }

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

    /**
     * @return false|string
     */
    public function createLoginToken()
    {
        $token = Authenticator::RandomToken(32);
        $this->session->set(SessionData::LOGIN_TOKEN, $token);
        return $token;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function loginToken()
    {
        return $this->session->get(SessionData::LOGIN_TOKEN);
    }

    public static function AuthorizeResource(string $contextName, array $user_data, bool $adminOK=true)
    {
        debug("AuthorizeContext using contextName: $contextName");

        if ($adminOK) {
            include_once("lib/auth/AdminAuthenticator.php");
            $auth_admin = new AdminAuthenticator();
            if ($auth_admin->authorize()) {
                debug("AdminAuthenticator authorization success");
                return;
            }
            else {
                debug("AdminAuthenticator authorization failed");
            }
        }

        $auth_class = "lib/auth/$contextName.php";
        @include_once($auth_class);
        if (!class_exists($auth_class, false)) {
            $auth_class = "class/auth/$contextName.php";
            @include_once($auth_class);
            if (!class_exists($auth_class, false)) {
                throw new Exception("Unable to locate the authorization class");
            }
        }

        $auth = new $contextName;

        if ($auth instanceof Authenticator) {

            $context = $auth->authorize($user_data);
            if (!$context) {
                debug("Authorization failed");
                throw new Exception("This resource is protected. Please login first.");
            }
            debug("Authorization success");
            return $context;

        }
        else {
            throw new Exception("No suitable authenticator class");
        }

    }
}

?>
