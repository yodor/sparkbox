<?php
include_once("lib/handlers/RequestHandler.php");

include_once("lib/panels/ConfirmMessageDialog.php");

class AuthenticatorRequestHandler extends RequestHandler
{

  private $auth = NULL;

  private $randsalt = NULL;
  private $username = NULL;
  private $pass = NULL;
  private $remember = NULL;

  public function __construct(Authenticator $auth, $cmd="doLogin")
  {
	  parent::__construct($cmd);
	  $this->auth = $auth;

  }

  protected function parseParams()
  {

	  if (!isset($_SESSION[$this->auth->getAuthContext()]["rand"])) throw new Exception("Session Timeout");

	  if (!isset($_REQUEST["username"]))throw new Exception("Username not passed");
	  if (!isset($_REQUEST["pass"]))throw new Exception("Password not passed");


	  $this->randsalt = $_SESSION[$this->auth->getAuthContext()]["rand"];

	  $this->username = $_POST["username"];
	  $this->pass = substr($_POST["pass"],0,32);

	  $this->remember = isset($_POST["remember"]);

  }
  public function createAction($title="Toggle", $href_add="",  $check_code="return 1;", $parameters_array=array() )
  {

	 return NULL;

  }
  protected function process()
  {

        $success = false;
        try {

            //throws exception on login error
            $success = $this->auth->authenticate($this->username, $this->pass, $this->randsalt, $this->remember);

            unset($_SESSION[$this->auth->getAuthContext()]["rand"]);

            //debug
            // echo "Loging success";
            // exit;

        }
        catch (Exception $e) {
            sleep(1);
            throw $e;
        }

        return $success;

  }




}
?>
