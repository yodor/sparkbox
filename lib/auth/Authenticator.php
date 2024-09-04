<?php
include_once("auth/AuthContext.php");
include_once("auth/AuthToken.php");
include_once("utils/SessionData.php");

/**
 * Abstract class for doing authentication
 * Reimplement to get context specific authenticator
 */
abstract class Authenticator
{
    /**
     * @var DBTableBean
     */
    protected DBTableBean $bean;

    protected SessionData $session;

    public function __construct(string $contextName, DBTableBean $bean)
    {
        $this->bean = $bean;
        $this->session = new SessionData($contextName);
    }

    public static function HMAC(string $key, string $data, string $hash_algo = 'md5')
    {
        return hash_hmac($hash_algo, $data, $key);
    }

    /**
     * Return random string from sha256 hash of random data
     * @param int $length Default 8 symbols
     * @return string
     */
    public static function RandomToken(int $length = 8) : string
    {
        // Generate string with random data
        $hash_result = hash('sha256', microtime_float() . "|" . rand());

        $hash_len = strlen($hash_result);

        if ($length>$hash_len) {
            $length = $hash_len;
        }

        // Position Limiting
        $start_point = $hash_len - $length;

        // Take a random starting point in the randomly
        // Generated String, not going any higher than $start_point
        return substr($hash_result, rand(0, $start_point), $length);
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
            if (str_starts_with($key, $this->session->name())) {
                Session::ClearCookie($key);
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
            debug("Error: " . $this->bean->getDB()->getError());
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
            debug($this, "SessionData does not have auth_token set");
            return NULL;
        }

        $auth = $this->session->get(SessionData::AUTH_TOKEN);

        $token = unserialize($auth);

        if (!($token instanceof AuthToken)) {
            debug($this, "AuthContext un-serialize failed");
            return NULL;
        }

        debug($this, "AuthToken un-serialized from session");

        if ($token->validateCookies($this->session->name()) !== TRUE) {
            debug($this, "AuthContext validation failed");
            return NULL;
        }

        debug($this, "Cookie validation success");
        return new AuthContext($token->getID(), $this->session);

    }

    /**
     * @param string $username
     * @param string $pass - HMAC of rand from client
     * @param $rand
     * @param bool $remember_me
     * @param bool $check_password_only
     * @throws Exception
     */
    public function login(string $username, string $pass, string $rand, bool $remember_me = FALSE, bool $check_password_only = FALSE)
    {

        debug($this, "Using loginToken: " . $rand);

        $db = DBConnections::Get();

        $username = $db->escape($username);

        $qry = $this->bean->queryFull();
        $qry->select->where()->add("email", "'$username'");
        $qry->select->limit = 1;

        try {
            $qry->exec();
            if (!($row = $qry->next())) throw new Exception("Username or password not recognized");

            $userID = $row[$this->bean->key()];

            $stored_user = $row["email"];
            //compute HMAC of $rand using hash of the password from $row["password"] as key
            $stored_pass = Authenticator::HMAC($row["password"], $rand);

            if (strcmp($stored_user, $username) !== 0 || strcmp($stored_pass, $pass) !== 0) {
                throw new Exception(tr("Username or password not recognized"));
            }

            if (isset($row["confirmed"])) {
                $is_confirmed = (int)$row["confirmed"];
                if ($is_confirmed < 1) {
                    $msg = tr("Your account is not activated yet.");
                    if (defined("ACCOUNT_CONFIRM_URL")) {
                        $link = ACCOUNT_CONFIRM_URL;
                        $msg .= "<BR>";
                        $msg .= tr("For more details visit the account activation page") . ": ";
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

            $this->updateLastSeen($userID, $db);

        }
        catch (Exception $e) {
            sleep(3);
            throw $e;
        }
    }

    protected function updateLastSeen(int $userID, DBDriver $db)
    {

        $update = new SQLUpdate($this->bean->select());
        $update->set("counter", "counter+1");
        $update->set("last_active", "CURRENT_TIMESTAMP");
        $update->where()->add($this->bean->key(), $userID);

        $db->transaction();
        $db->query($update->getSQL());
        $db->commit();
    }

    /**
     * Create new AuthToken for user ID $id.
     * Serialize the token to the SessionData of this Authenticator
     * @param int $id
     */
    protected function createAuthToken(int $id)
    {
        debug($this, "Regenerating session ID");

        session_regenerate_id(TRUE);

        $token = new AuthToken($id);

        debug($this, "Creating cookies for SessionData name: " . $this->session->name());
        $token->storeCookies($this->session->name());

        debug($this, "Serializing auth_token in SessionData");
        $this->session->set(SessionData::AUTH_TOKEN, serialize($token));
    }

    protected function fillSessionData(array $row)
    {
        debug($this, "fill common SessionData");

        if (isset($row[SessionData::EMAIL])) {
            $this->session->set(SessionData::EMAIL, $row[SessionData::EMAIL]);
        }
        if (isset($row[SessionData::FULLNAME])) {
            $this->session->set(SessionData::FULLNAME, $row[SessionData::FULLNAME]);
        }

    }

    public function fbAuthenticate($oauth_token)
    {

        $graph_url = "https://graph.facebook.com/me?access_token=$oauth_token";
        $user_fb = json_decode(file_get_contents($graph_url));

        $bean = new UsersBean();
        $email = $user_fb->email;

        $qry = $bean->queryField("email", $email, 1);
        $qry->exec();

        if (!($urow = $qry->next())) throw new Exception("This email is not registered or not confirmed yet");

        $userID = (int)$urow[$bean->key()];

        $authstore["id"] = $userID;
        $authstore["fbID"] = (int)$urow["fb_userID"];

        $s1 = "UPDATE users SET counter=counter+1 , last_active=CURRENT_TIMESTAMP, oauth_token='$oauth_token' WHERE " . $bean->key() . "='$userID'";
        $db = DBConnections::Get();

        $db->transaction();
        $ret = $db->query($s1);
        if (!$ret) throw new Exception($db->getError());
        $db->commit();

        $this->createContext($userID, $authstore);

        $this->updateLastSeen($userID);

        return $urow;

    }

    /**
     * Create new random token and store as variable 'LOGIN_TOKEN' in SessionData
     * Use this token in AuthForm HIDDEN field (rand)
     * @return string
     */
    public function createLoginToken() : string
    {
        $token = Authenticator::RandomToken(32);
        $this->session->set(SessionData::LOGIN_TOKEN, $token);
        return $token;
    }

    /**
     * Get the 'LOGIN_TOKEN' variable from SessionData
     * @return mixed
     * @throws Exception
     */
    public function loginToken()
    {
        return $this->session->get(SessionData::LOGIN_TOKEN);
    }

    public static function AuthorizeResource(string $contextName, array $user_data, bool $adminOK = TRUE) : ?AuthContext
    {
        debug("AuthorizeContext using contextName: $contextName");

        //administrator access
        if ($adminOK) {
            debug("Trying AdminAuthenticator first");

            include_once("auth/AdminAuthenticator.php");
            $auth_admin = new AdminAuthenticator();
            $context = $auth_admin->authorize();
            if ($auth_admin->authorize()) {
                debug("AdminAuthenticator authorization success");
                return $context;
            }
            else {
                debug("AdminAuthenticator authorization failed");
            }
        }

        try {
            $globals = SparkGlobals::Instance();
            $globals->includeBeanClass($contextName);
        }
        catch (Exception $e) {
            debug("Authenticator class can not be loaded");
            throw new Exception("Unable to locate the authorization class");
        }

        $auth = new $contextName();

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
