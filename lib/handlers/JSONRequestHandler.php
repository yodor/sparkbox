<?php
include_once("lib/beans/SiteTextsBean.php");
include_once("lib/beans/TranslationBeansBean.php");
include_once("lib/handlers/RequestHandler.php");


abstract class JSONRequestHandler extends RequestHandler
{

  protected $supported_content = NULL;
  protected $content_type = "";
  protected $response_send = false;
  
  public function __construct($cmd)
  {
      parent::__construct($cmd);
      
      $this->supported_content = array();
      
      
      $class_methods = get_class_methods($this);
      foreach($class_methods as $key=>$fname) {
	  if (strpos($fname, "_")===0 && strpos($fname, "__")===false) {
	    $supported_content = str_replace("_", "", $fname);
	    $this->supported_content[] = $supported_content;


	  }
      }
      
      	    debugArray("JSONRequestHandler::CTOR for: ".get_class($this)." SupportedContent: ", $this->supported_content);

  }


  protected function parseParams() 
  {

      if (!isset($_GET["type"])) throw new Exception("Content Type not passed");
      $content_type = $_GET["type"];

      if (!in_array($content_type, $this->supported_content)) throw new Exception("Content Type not supported");

      $this->content_type = $content_type;
      
      debug(get_class($this)."::parseParams: content_type requested: ".$this->content_type);
  }
  public function shutdown()
  {
      $err = error_get_last();

      
      
      if (is_array($err)) {
      
	  if ($this->response_send) {
	      debugArray("JSONRequestHandler::shutdown => Error Found after response: ", $err);
	  }
	  else {
	      @ob_end_clean();
	      $ret = new JSONResponse(get_class($this)."Response");
	      $ret->status = JSONResponse::STATUS_ERROR;
	      $ret->message = "Error: ".$err["type"]." - ".$err["message"]."<BR>File: ".$err["file"]." Line: ".$err["line"];
	      $ret->response();
	      $ret->contents = "";
	      
	  }
	  

      }
      exit;
  }
  protected function process()
  {
      $ret = new JSONResponse(get_class($this)."Response");
      
      ob_start();
      
      register_shutdown_function(array($this, "shutdown"));


      try {

	  $function_name = "_".$this->content_type;
	  
	  if (is_callable(array($this, $function_name))) {
	    $this->$function_name($ret);
	  }
	  else {
	    throw new Exception("Function: $function_name not callable");
	  }

	  $ret->contents = ob_get_contents();
	  $ret->status = JSONResponse::STATUS_OK;

      }
      catch (Exception $e) {

	  debugOutput(get_class($this)."::process: Error:".$e->getMessage());
	  
	  $ret->contents = "";
	  $ret->status = JSONResponse::STATUS_ERROR;
	  $ret->message = $e->getMessage();

      }

      ob_end_clean();
      $ret->response();
      $this->response_send = true;
      
  }

}
?>