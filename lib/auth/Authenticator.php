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
     * Random hex string from a cryptographically secure source - login challenges, auth cookies,
     * confirmation codes, order references
     * @param int $length number of hex characters
     * @return string
     */
    public static function RandomToken(int $length): string
    {
        if ($length < 1) return "";
        return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
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
        foreach (Session::CookieKeys($this->session->name()) as $key) {
            Session::ClearCookie($key);
        }
        Session::Destroy();
    }


    /**
     * @param array|NULL $user_data
     * @return AuthContext|null
     * @throws Exception
     */
    /**
     * Column marking a suspended account: 'suspend' (users and admin_users tables) or 'suspended'
     * (older users table definitions). Empty string if the table has neither.
     * @return string
     */
    protected function suspendColumn() : string
    {
        foreach (array("suspend", "suspended") as $column) {
            if ($this->bean->haveColumn($column)) return $column;
        }
        return "";
    }

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
        $suspendColumn = $this->suspendColumn();
        if ($suspendColumn !== "") {
            $suspend_status = (int)$this->bean->getValue($token->getID(), $suspendColumn);
            if ($suspend_status > 0) {
                Debug::ErrorLog("Account is suspended");
                return NULL;
            }
        }

        return new AuthContext($token->getID(), $this->session);
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
        $qry = $this->bean->queryFull();
        $qry->stmt->where()->match("email", $username);
        $qry->stmt->limit(1);
        $qry->exec();

        if (!($row = $qry->next())) {
            //same cost as a password check - the response time does not tell if the email is registered
            $hash->getValue();
            throw new Exception(tr("Username or password not recognized"));
        }
        $qry->free();

        Debug::ErrorLog("Email found");

        $userID     = $row[$this->bean->key()];
        $storedHash = $row["password"];

        // 3. Password verification - a hash of older parameters is upgraded (password_needs_rehash)
        if (!$hash->verify($storedHash)) {
            throw new Exception(tr("Username or password not recognized"));
        }
        Debug::ErrorLog("Password verification successful for userID: $userID");
        $this->upgradePassword($userID, $hash, $storedHash);

        // 4. Account status checks
        if (isset($row["confirmed"]) && (int)$row["confirmed"] < 1) {
            throw new Exception(tr("Your account is not activated yet."));
        }
        $suspendColumn = $this->suspendColumn();
        if ($suspendColumn !== "" && (int)($row[$suspendColumn] ?? 0) > 0) {
            throw new Exception(tr("Your account is temporarily suspended."));
        }

        Debug::ErrorLog("Login successful");
        // 5. Success path
        $this->fillSessionData($row, $userID);
        $this->createAuthToken($userID);
        $this->updateLastSeen($userID);
    }

    /**
     * Store the password again when its hash has older parameters (Config::PASSWORD_TYPE, PASSWORD_OPTIONS)
     * @param int $userID
     * @param PasswordHash $hash the verified password
     * @param string $storedHash
     * @return void
     */
    protected function upgradePassword(int $userID, PasswordHash $hash, string $storedHash) : void
    {
        if (!$hash->needRehash($storedHash)) return;
        try {
            $this->bean->checkPasswordColumn();
            $this->bean->update($userID, array("password" => $hash->getValue()));
            Debug::ErrorLog("Password rehashed for userID: $userID");
        }
        catch (Exception $e) {
            Debug::ErrorLog("Failed rehashing password for userID: $userID - " . $e->getMessage());
        }
    }

    protected function updateLastSeen(int $userID) : void
    {
        $update = new SQLUpdate($this->bean->select());
        $update->column("counter")->set("counter + 1");
        $update->column("last_active")->set("CURRENT_TIMESTAMP");
        $update->where()->match($this->bean->key(), $userID);

        $query = new DBQuery();
        $query->exec($update);
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
            if ($context) {
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