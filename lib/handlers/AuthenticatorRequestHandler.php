<?php
include_once("handlers/RequestHandler.php");

include_once("panels/ConfirmMessageDialog.php");

class AuthenticatorRequestHandler extends RequestHandler
{

    /**
     * @var Authenticator
     */
    protected $auth;

    protected $randsalt = "";

    protected $email = "";
    protected $pass = "";
    protected $remember = FALSE;

    public function __construct(Authenticator $auth, $cmd = "doLogin")
    {
        parent::__construct($cmd);
        $this->auth = $auth;
    }

    public function getAuthenticator(): Authenticator
    {
        return $this->auth;
    }

    /**
     * @throws Exception
     */
    protected function parseParams()
    {
        if (!isset($_REQUEST["email"])) throw new Exception("Username not passed");
        if (!isset($_REQUEST["pass"])) throw new Exception("Password not passed");

        $this->randsalt = $this->auth->loginToken();

        $this->email = $_POST["email"];
        $this->pass = substr($_POST["pass"], 0, 32);

        $this->remember = isset($_POST["remember"]);
    }

    public function createAction($title = "Toggle", $href_add = "", $check_code = NULL, $parameters_array = array())
    {

        return NULL;

    }

    protected function process()
    {

        $success = FALSE;
        try {

            //throws exception on login error
            $this->auth->login($this->email, $this->pass, $this->randsalt, $this->remember);
            $success = TRUE;
        }
        catch (Exception $e) {
            sleep(1);
            throw $e;
        }

        return $success;

    }

}

?>
