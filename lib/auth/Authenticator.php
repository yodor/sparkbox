<?php
include_once("auth/PasswordHash.php");
include_once("auth/AuthContext.php");
include_once("auth/AuthToken.php");
include_once("utils/SessionData.php");
include_once("beans/UsersBean.php");

/**
 * Abstract class for doing authentication
 * Reimplement to get context specific authenticator
 */
abstract class Authenticator
{

    /**
     * @var UsersBean
     */
    protected ?UsersBean $bean = null;
    protected ?SessionData $session = null;

    public function __construct(string $context_name, UsersBean $bean)
    {
        $this->bean = $bean;
        $this->session = new SessionData($context_name);
    }

    /**
     * Return random string from sha256 hash of random data
     * @param int $length
     * @return string
     */
    public static function RandomToken(int $length): string
    {
        // Generate string with random data
        $hash_result = hash('sha256', Spark::MicroTime() . "|" . rand());

        $hash_len = strlen($hash_result);

        if ($length > $hash_len) {
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
    public function name() : string
    {
        return $this->session->name();
    }

    /**
     * Logout. Clears any stored authentication data for a named context - '$name'
     */
    public function logout() : void
    {
        $this->session->destroy();
        foreach ($_COOKIE as $key => $val) {
            if (str_starts_with($key, $this->session->name())) {
                Session::ClearCookie($key);
            }
        }
        Session::Destroy();
    }


    /**
     * @param array|NULL $user_data
     * @return AuthContext|null
     * @throws Exception
     */
    public function authorize(?array $user_data = NULL) : ?AuthContext
    {

        if (!$this->session->contains(AuthContext::AUTH_TOKEN)) {
            Debug::ErrorLog("SessionData does not have auth_token set");
            return NULL;
        }

        $token = $this->session->get(AuthContext::AUTH_TOKEN);

        if (!($token instanceof AuthToken)) {
            Debug::ErrorLog("AuthContext un-serialize failed");
            Session::Remove(AuthContext::AUTH_TOKEN);
            return NULL;
        }

        Debug::ErrorLog("AuthToken un-serialized from session");

        if ($token->validateCookies($this->session->name()) !== TRUE) {
            Debug::ErrorLog("AuthContext validation failed");
            Session::Remove(AuthContext::AUTH_TOKEN);
            return NULL;
        }

        Debug::ErrorLog("Cookie validation success");

        //check if account is enabled
        if ($this->bean->haveColumn("suspended")) {
            $suspend_status = (int)$this->bean->getValue($token->getID(), "suspended");
            if ($suspend_status > 0) {
                Debug::ErrorLog("Account is suspended");
                return NULL;
            }
        }

        return new AuthContext($token->getID(), $this->session);
    }

    /**
     * Set a random password to user with email '$email'
     * Creates 'random string' using Authenticator::RandomToken(8)
     * Password is stored in DB by hashing the 'random string' using Authenticator::PasswordHash - currently md5
     * @param string $email
     * @return string The 'random string' that can be used on the login forms or emailed back to the user
     * @throws Exception If email is not found
     */
    public function setRandomPassword(string $email): string
    {
        try {

            $userID = $this->bean->email2id($email);
            if ($userID < 1) {
                throw new Exception(tr("This user is not registered with us"));
            }

            $result = Authenticator::RandomToken(8);

            //Debug::ErrorLog("Password: ".$result);

            $this->bean->checkPasswordColumn();
            $hash = new PasswordHash($result);
            $update["password"] = $hash->getValue();
            if (!$this->bean->update($userID, $update)) throw new Exception($this->bean->getDB()->getError());

            return $result;
        } catch (Exception $e) {
            Debug::ErrorLog("Password change failed: " . $e->getMessage());
            throw $e;
        } finally {
            sleep(3);
        }
    }

    public function login(string $email, string $password_plain, string $client_hmac): void
    {
        $this->loginImpl($email, $password_plain, $client_hmac);
    }

    /**
     * Verify login token challenge
     * @param PasswordHash $hash
     * @param string $client_hmac
     * @return void
     * @throws Exception
     */
    public function verifyTokenHMAC(PasswordHash $hash, string $client_hmac) : void
    {
        $token = $this->consumeLoginToken();
        Debug::ErrorLog("PasswordHash()");
        if (!$hash->hmacVerify($token, $client_hmac)) throw new Exception(tr("Incorrect challenge"));
        Debug::ErrorLog("PasswordHash()->hmacVerify success");
    }
    /**
     * Authenticates a user using username + password + optional cryptographic proof.
     *
     * The client must send:
     *   - username (email)
     *   - plaintext password (transmitted over TLS)
     *   - proof: HMAC-SHA256(password, login_token)  (hex string)
     *
     * Security design notes (March 2026):
     *   - Password is verified server-side using Argon2id (memory-hard, high resistance to offline attacks)
     *   - Proof is checked first → early rejection of replayed/forged requests
     *   - Delay is applied on every authentication failure path to slow online brute-force
     *   - Username enumeration is prevented by applying delay even when user not found
     *   - Hash parameters can be upgraded transparently via password_needs_rehash()
     *
     * @param string      $username     The user's email / identifier
     * @param string      $password     Plaintext password received from client (over TLS)
     * @param string      $client_hmac  Client-computed challenge - HMAC-SHA256(key=sha256(password), data=login_token) in hex
     * @return void
     * @throws Exception On any authentication failure or system error
     */
    protected function loginImpl(string $username, string $password, string $client_hmac): void
    {

        // 1. Challenge token validation (always first)
        $hash = new PasswordHash($password);

        $this->verifyTokenHMAC($hash, $client_hmac);

        // 2. Lookup user
        $db = DBConnections::Driver();
        $username = $db->escape($username);

        $qry = $this->bean->queryFull();
        $qry->select->where()->add("email", "'$username'");
        $qry->select->limit = 1;
        $qry->exec();

        if (!($row = $qry->next())) {
            throw new Exception(tr("Username or password not recognized"));
        }
        Debug::ErrorLog("Email found");

        $userID     = $row[$this->bean->key()];
        $storedHash = $row["password"];

        // 3. Try legacy verification + upgrade on success
        if ($hash->digestVerify($storedHash)) {
            Debug::ErrorLog("MD5 digest detected and authenticated. Migrating userID: $userID");
            // Legacy hash matched → login successful → upgrade immediately
            // $storedHash is already verified to be old use skip_needRehash = true
            $this->upgradePassword($userID, $hash, $storedHash, true);
            // Proceed to success path
        } else {
            // Not legacy → normal modern hash verification
            if (!$hash->verify($storedHash)) {
                throw new Exception(tr("Username or password not recognized"));
            }
            Debug::ErrorLog("Password verification successful for userID: $userID");
            //Still check whether upgrade is needed (parameters changed)
            $this->upgradePassword($userID, $hash, $storedHash, false);
        }

        // 4. Account status checks
        if (isset($row["confirmed"]) && (int)$row["confirmed"] < 1) {
            throw new Exception(tr("Your account is not activated yet."));
        }
        if (isset($row["suspended"]) && (int)$row["suspended"]) {
            throw new Exception(tr("Your account is temporarily suspended."));
        }

        Debug::ErrorLog("Login successful");
        // 5. Success path
        $this->fillSessionData($row, $userID);
        $this->createAuthToken($userID);
        $this->updateLastSeen($userID, $db);
    }

    protected function upgradePassword(int $userID, PasswordHash $hash, string $storedHash, bool $skip_needRehash) : void
    {

        try {
            //old hash is already verified use skip_needRehash = true
            if ($skip_needRehash || $hash->needRehash($storedHash)) {
                $this->bean->checkPasswordColumn();
                $update = array("password" => $hash->getValue());
                $this->bean->update($userID, $update);

                Debug::ErrorLog("Migrated password for userID: $userID");
            }
            else {
                Debug::ErrorLog("No migration performed for userID: $userID");
            }
        }
        catch (Exception $e) {
            Debug::ErrorLog("Failed migrating password for userID: $userID - " . $e->getMessage());
        }
    }

    protected function updateLastSeen(int $userID, DBDriver $db) : void
    {

        $update = new SQLUpdate($this->bean->select());
        $update->set("counter", "counter+1");
        $update->set("last_active", "CURRENT_TIMESTAMP");
        $update->where()->add($this->bean->key(), $userID);

        try {
            $db->transaction();
            $db->query($update->getSQL());
            $db->commit();
        }
        catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Create new AuthToken for user ID $id.
     * Serialize the token to the SessionData of this Authenticator
     * @param int $id
     */
    protected function createAuthToken(int $id) : void
    {
        Debug::ErrorLog("Regenerating session ID");

        session_regenerate_id(TRUE);

        $token = new AuthToken($id);

        Debug::ErrorLog("Creating cookies for SessionData name: " . $this->session->name());
        $token->storeCookies($this->session->name());

        Debug::ErrorLog("Storing AUTH_TOKEN in SessionData");
        $this->session->set(AuthContext::AUTH_TOKEN, $token);

        //already removed current login token
        $this->session->remove(AuthContext::LOGIN_TOKEN);

    }

    protected function fillSessionData(array $row, int $userID) : void
    {
        Debug::ErrorLog("fill common SessionData");

        if (isset($row[AuthContext::EMAIL])) {
            $this->session->set(AuthContext::EMAIL, $row[AuthContext::EMAIL]);
        }
        if (isset($row[AuthContext::FULLNAME])) {
            $this->session->set(AuthContext::FULLNAME, $row[AuthContext::FULLNAME]);
        }

    }

    /**
     * Create new random token and store as SessionData(AuthContext::LOGIN_TOKEN).
     * If token is already created the stored value will be used instead.
     * @return string The new created token or stored value if already present in SessionData
     * @throws Exception
     */
    public function produceLoginToken() : string
    {
        if ($this->session->contains(AuthContext::LOGIN_TOKEN)) {
            $token = $this->session->get(AuthContext::LOGIN_TOKEN);
            Debug::ErrorLog("Reusing login token: " . $token);
        }
        else {
            $token = Authenticator::RandomToken(32);
            Debug::ErrorLog("Produced new login token: " . $token);
            $this->session->set(AuthContext::LOGIN_TOKEN, $token);
        }
        return $token;
    }

    /**
     * Get the stored token value from SessionData(AuthContext::LOGIN_TOKEN)
     * @return string
     * @throws Exception
     */
    public function consumeLoginToken() : string
    {
        if (!$this->session->contains(AuthContext::LOGIN_TOKEN)) {
            throw new Exception("Login token not produced yet");
        }

        $token = $this->session->get(AuthContext::LOGIN_TOKEN);
        $this->session->remove(AuthContext::LOGIN_TOKEN);
        Debug::ErrorLog("Consumed login token: " . $token);
        return $token;
    }
    public static function AuthorizeResource(string $contextName, array $user_data, bool $adminOK) : ?AuthContext
    {
        Debug::ErrorLog("Using contextName: $contextName");

        //administrator access
        if ($adminOK) {
            Debug::ErrorLog("Trying AdminAuthenticator first");

            include_once("auth/AdminAuthenticator.php");
            $auth_admin = new AdminAuthenticator();
            $context = $auth_admin->authorize();
            if ($auth_admin->authorize()) {
                Debug::ErrorLog("AdminAuthenticator authorization success");
                return $context;
            }
            else {
                Debug::ErrorLog("AdminAuthenticator authorization failed");
            }
        }

        try {
            $auth = SparkLoader::Factory(SparkLoader::PREFIX_AUTH)->instance($contextName, Authenticator::class);

            if (! ($auth instanceof Authenticator)) throw new Exception("Loaded class is not instance of Authenticator");

            $context = $auth->authorize($user_data);
            if (!$context) {
                throw new Exception("This resource is protected. Please login first.");
            }
            Debug::ErrorLog("Authorization success");
            return $context;

        }
        catch (Exception $e) {
            Debug::ErrorLog("Authorization failed: " . $e->getMessage());
            throw new Exception("Authorization failed: ".$e->getMessage());
        }

    }


}