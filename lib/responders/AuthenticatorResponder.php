<?php
include_once("responders/RequestResponder.php");

include_once("dialogs/ConfirmMessageDialog.php");

class AuthenticatorResponder extends RequestResponder
{

    /**
     * @var Authenticator|null
     */
    protected ?Authenticator $auth = null;

    protected string $email = "";
    protected string $challenge = "";

    public function __construct(Authenticator $auth)
    {
        parent::__construct();
        $this->auth = $auth;
        //success and cancel urls set outside
    }

    public function getAuthenticator(): Authenticator
    {
        return $this->auth;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        if (!isset($_REQUEST["email"])) throw new Exception("Email not passed");
        if (!isset($_REQUEST["password"])) throw new Exception("Password not passed");
        if (!isset($_REQUEST["challenge"])) throw new Exception("Challenge not passed");

        $this->email = $_REQUEST["email"];
        $this->challenge = $_REQUEST["challenge"];

    }

    public function createAction(string $title = "") : ?Action
    {
        return NULL;
    }

    /**
     * Do login and rethrow exception on failure
     * @return void
     * @throws Exception
     */
    protected function processImpl() : void
    {

        try {
            $password = substr($_REQUEST["password"], 0, 255);
            unset($_REQUEST["password"]);
            $this->auth->login($this->email, $password, $this->challenge);
        }
        catch (Exception $e) {
            //TODO: ratelimit
            Debug::ErrorLog("Login failed: ".$e->getMessage());
            sleep(3);
            throw $e;
        }
        finally {
            unset($password);
        }

    }


}