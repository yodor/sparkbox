<?php
include_once("handlers/RequestHandler.php");

include_once("panels/ConfirmMessageDialog.php");

class AuthenticatorRequestHandler extends RequestHandler
{

    /**
     * @var Authenticator
     */
    private $auth = NULL;

    private $randsalt = NULL;
    private $username = NULL;
    private $pass = NULL;
    private $remember = NULL;

    public function __construct(Authenticator $auth, $cmd = "doLogin")
    {
        parent::__construct($cmd);
        $this->auth = $auth;

    }

    /**
     * @throws Exception
     */
    protected function parseParams()
    {
        if (!isset($_REQUEST["username"])) throw new Exception("Username not passed");
        if (!isset($_REQUEST["pass"])) throw new Exception("Password not passed");

        $this->randsalt = $this->auth->loginToken();

        $this->username = $_POST["username"];
        $this->pass = substr($_POST["pass"], 0, 32);

        $this->remember = isset($_POST["remember"]);
    }

    public function createAction($title = "Toggle", $href_add = "", $check_code = "return 1;", $parameters_array = array())
    {

        return NULL;

    }

    protected function process()
    {

        $success = false;
        try {

            //throws exception on login error
            $this->auth->login($this->username, $this->pass, $this->randsalt, $this->remember);
            $success = true;
        }
        catch (Exception $e) {
            sleep(1);
            throw $e;
        }

        return $success;

    }


}

?>
