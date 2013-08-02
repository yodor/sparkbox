<?php
include_once("lib/handlers/JSONResponse.php");
include_once("lib/handlers/IRequestProcessor.php");
include_once("lib/utils/ICallable.php");

abstract class RequestHandler implements IRequestProcessor
{
  protected $cmd = NULL;

  protected $cancel_url = "";
  protected $success_url = "";

  protected $auth_handler = NULL;

  protected $need_confirm = false;
  
  public function __construct($cmd)
  {
      $this->cmd=$cmd;

  }
  public function setNeedConfirm($mode)
  {
      $this->need_confirm = ($mode) ? true : false;
  }
  public function getCommandName()
  {
      return $this->cmd;
  }
  
  public function setAuthorizationHandler(ICallable $handler)
  {
      $this->auth_handler = $handler;
  }
  
  public function getAuthorizationHandler()
  {
      return $this->auth_handler;
  }
  
  public function setCancelUrl($url)
  {
      $this->cancel_url=$url;
  }
  
  public function getCancelUrl()
  {
      return $this->cancel_url;
  }
  
  public function setSuccessUrl($url)
  {
      $this->success_url=$url;
  }
  
  public function getSuccessUrl()
  {
      return $this->success_url;
  }
  
  public function shouldProcess()
  {
      if (isset($_REQUEST["cmd"]) && strcmp($_REQUEST["cmd"],$this->cmd)==0 ) {

	  return TRUE;
      }
      return FALSE;
  }
  
  public function processHandler()
  {
	$this->parseParams();
	
	if ($this->auth_handler instanceof ICallable)	{
	    $this->auth_handler->execute($this);
	}
	
	
	$do_process = false;
	
	if ($this->need_confirm && !isset($_POST["confirm_handler"])) {

	    $this->processConfirmation();
	}
	else {
	    $do_process = true;
	}

	if (!$do_process) return;
	
	$this->process();

	if (strlen($this->getSuccessUrl())>0) {
	    header("Location: ".$this->getSuccessUrl());
	    exit;
	}

	if (strlen($this->getCancelUrl())>0) {
	    header("Location: ".$this->getCancelUrl());
	    exit;
	}
  }
  public function createAction($title=false, $href=false, $check_code="return 1;", $parameters_array=array())
  {
	return NULL;
  }

  abstract protected function process();
  abstract protected function parseParams();
  
  protected function processConfirmation()
  {
	  $this->drawConfirmDialog();
  }
  
  public function drawConfirmDialog($title="Confirm Action", $text="Confirm action?")
  {
	  $md = new ConfirmMessageDialog($title, "msg_confirm");

	  $btn_ok = $md->getButtonAt(0);
	  $btn_ok->setText("Confirm");
	  $btn_ok->setHref("javascript:confirmHandler()");
	  $btn_cancel = $md->getButtonAt(1);
	  $btn_cancel->setText("Cancel");
	  $btn_cancel->setHref("javascript:cancelHandler()");

	  
	  $md->startRender();
	  
	  
	  echo "<form id=confirm_handler_form method=post>";

	  echo $text;

	  echo "<br>";


	  echo "<input type=hidden name=confirm_handler value=1>";
	  echo "</form>";
	  ?>
	  <script language=javascript defer=1>
	  function confirmHandler()
	  {
		  var frm = document.getElementById("confirm_handler_form");
		  frm.submit();
	  }
	  function cancelHandler()
	  {
		  document.location.replace("<?php echo $this->cancel_url;?>");
	  }
	  addLoadEvent(function(){
	      showPopupPanel("msg_confirm");
	  });
	  </script>
	  
	  <?php

	  $md->finishRender();

	  unset($_GET["cmd"]);
  }
}
?>