<?php
include_once("responders/RequestResponder.php");

include_once("dialogs/ConfirmMessageDialog.php");

class AuthenticatorResponder extends RequestResponder
{

    /**
     * @var Authenticator
     */
    protected Authenticator $auth;

    protected string $randsalt = "";

    protected string $email = "";
    protected string $pass = "";
    protected bool $remember = FALSE;

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
        if (!isset($_REQUEST["email"])) throw new Exception("Username not passed");
        if (!isset($_REQUEST["pass"])) throw new Exception("Password not passed");

        $this->randsalt = $this->auth->loginToken();

        $this->email = $_POST["email"];
        $this->pass = substr($_POST["pass"], 0, 32);

        $this->remember = isset($_POST["remember"]);
    }

    public function createAction(string $title = "") : ?Action
    {

        return NULL;

    }

    /**
     * @return void
     * @throws Exception
     */
    protected function processImpl() : void
    {
        //throws exception on login error
        $this->auth->login($this->email, $this->pass, $this->randsalt, $this->remember);

    }


}