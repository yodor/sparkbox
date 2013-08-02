<?php
include_once("lib/utils/ImageResizer.php");
include_once("lib/utils/Session.php");
include_once("lib/storage/StorageObject.php");
include_once("lib/storage/FileStorageObject.php");
include_once("lib/storage/ImageStorageObject.php");

class Storage
{
  protected $className=false;
  protected $id=-1;
  protected $cmd=false;
  protected $headers;
  protected $row;
  protected $blob_field = "photo";
  protected $cache_hash = "";
  protected $skip_cache = false;
  protected $disposition = "inline";
  protected $gray_filter = false;
  protected $use_storage = false;

  public function __construct()
  {
	  $this->headers = array();
	  $this->row = array();
  }
  public function processRequest()
  {
	  try {

		if (!isset($_GET["cmd"]))
		{
			throw new Exception("No command passed.");

		}
		$this->cmd = $_GET["cmd"];
		if (!isset($_GET["id"]) || !isset($_GET["class"])) {
			throw new Exception("id and class parameters required.");
		}

		if (isset($_GET["skip_cache"])) {
			$this->skip_cache=true;
		}

		$this->id=(int)$_GET["id"];
		$this->className = $_GET["class"];

		$this->cache_hash = $this->className."-".$this->id;

if (isset($_GET["max-width"])) {
  ImageResizer::$max_width=(int)$_GET["max-width"];
}
if (isset($_GET["max-height"])) {
  ImageResizer::$max_height=(int)$_GET["max-height"];
}


ImageResizer::$gray_filter=false;

if (isset($_GET["gray_filter"])) {
  ImageResizer::$gray_filter=true;

  $this->cache_hash = $this->className."-".$this->id."-gray";
}


// debug("Storage::processRequest: ".$_SERVER["REQUEST_URI"]);


		if (strcmp($this->cmd,"data_file")==0) {
			$this->skip_cache=true;
			$this->disposition = "attachment";
			$this->blob_field = "data";
			$this->loadBeanClass();
		}
		else if (strcmp($this->cmd, "gallery_photo")==0) {
// 			$this->skip_cache=false;

			
			if (ImageResizer::$max_width>0 && ImageResizer::$max_height>0) {
			    $size_w = (int)$_GET["max-width"];
			    $size_h = (int)$_GET["max-height"];
			    $this->cache_hash.=$size_w."-".$size_h;
			}

			$this->blob_field = "photo";
			$this->loadBeanClass();

			if (ImageResizer::$max_width>0 && ImageResizer::$max_height>0) {
			  ImageResizer::autoCrop($this->row);
			  debug("Storage::gallery_photo: ID:{$this->id} Fit Rectangle: ".$size_w."x".$size_h);
			}
		}
		else if (strcmp($this->cmd, "image_crop")==0) {
			if (!isset($_GET["width"]) || !isset($_GET["height"])) throw new Exception("Width parameter missing");
			$size_w = (int)$_GET["width"];
			$size_h = (int)$_GET["height"];
			// $this->skip_cache=true;
			$this->cache_hash.=$size_w."-".$size_h;
			$this->blob_field = "photo";

			$this->loadBeanClass();

			ImageResizer::$max_width = $size_w;
			ImageResizer::$max_height = $size_h;
			
			ImageResizer::crop($this->row);
		}
		else if (strcmp($this->cmd, "image_thumb")==0) {
			// $this->skip_cache=true;
			$size = -1;
			if (isset($_GET["width"])) {
			  $size = (int)$_GET["width"];
			}
			
			if (isset($_GET["size"])) {
			  $size = (int)$_GET["size"];
			}
			if ($size<1) throw new Exception("Width/Size parameter missing");
			$this->cache_hash.="-".$size;

			$this->blob_field = "photo";

			$this->loadBeanClass();

			ImageResizer::thumbnail($this->row, $size);
		}
		
		$this->sendResponse();
		
	  }
	  catch (ImageResizerException $e1) {
	    debug("ImageResizerException: ".$_SERVER["REQUEST_URI"]);
	    $this->sendNotFound();
	  }
	  catch (Exception $e) {
	    $this->sendError($e);
	  }
  }
  protected function loadBeanClass()
  {
	  $this->headers = array();
	  if (file_exists("class/beans/".$this->className.".php")) {
		  include_once("class/beans/".$this->className.".php");
	  }
	  else if (file_exists("lib/beans/".$this->className.".php")){
		  include_once("lib/beans/".$this->className.".php");
	  }
	  else {
		throw new Exception("Bean class not found: ".$this->className);
	  }
	  $this->bean = new $this->className();

if ($this->id == -1) {
  $funcname = "Default_".$this->className;
  if (is_callable($funcname)) {

	$funcname($this->row);

  }
  else throw new Exception("No default value for this class");

}
else {
	  $this->row = $this->bean->getByID($this->id);
}
	  $this->checkPermissions();

	  $this->checkCache();

	  $blob_field = $this->blob_field;

	  if (isset($_GET["blob_field"])) {
		  $blob_field = $_GET["blob_field"];

	  }

	  else {

		  if (!isset($this->row[$blob_field])) {
			  //search blob_field
			  $stypes = $this->bean->getStorageTypes();
			  foreach($stypes as $field_name => $field_type) {

				  if (strpos($field_type,"blob")!==false) {
					  $blob_field = $field_name;
					  break;
				  }
			  }
		  }

	  }
	  $storage_object = @unserialize($this->row[$blob_field]);

	  if ($storage_object instanceof StorageObject) {
	  
		  
// 		  $date_upload_row = false;
// 		  
// 		  if (isset($this->row["date_upload"])) {
// 		    $date_upload_row = $this->row["date_upload"];
// 		  }
		  
		  $this->row = array();
		  $storage_object->deconstruct($this->row, "photo", false);

// 		  if ($date_upload_row) {
// 		    $this->row["date_upload"] = $date_upload_row;
// 		  }
		  $this->cache_hash.="|".$blob_field;
		  
		  $this->checkCache();
		  
	  }
	  else {

		  //

		  
	  }



  }
  protected function checkPermissions()
  {

	  if (!isset($this->row["auth_context"])) return;
	  if (strlen($this->row["auth_context"])<1)return;

	  $session = new Session();

	  include_once("lib/AdminAuthenticator.php");
	  if (AdminAuthenticator::checkAuthState()) return;


	  @include_once("class/".$this->row["auth_context"].".php");
	  @include_once("lib/".$this->row["auth_context"].".php");
	  $auth = new $this->row["auth_context"];
	  if (!$auth->checkAuthState(true)) throw new Exception("This resource is protected. Please login first.");

  }
  protected function checkCache()
  {

$last_modified = date("D, d M Y H:i:s T");



	  if (isset($this->row["date_upload"])) {
		$last_modified = date("D, d M Y H:i:s T", strtotime($this->row["date_upload"]));
	  }
	  else if (isset($this->row["date_updated"])) {
		$last_modified = date("D, d M Y H:i:s T", strtotime($this->row["date_updated"]));
	  }
	  else if (isset($this->row["item_date"])) {
		$last_modified = date("D, d M Y H:i:s T", strtotime($this->row["item_date"]));
	  }



	  //always keep one year ahead from request time
	  $expire = date("D, d M Y H:i:s T", strtotime("+1 year", strtotime($last_modified)));

	  $etag = md5($this->cache_hash."-".$last_modified);


// 	  error_log("Storage::checkCache last_modified: $last_modified | expire: $expire | etag: $etag",4);

	  // check if the last modified date sent by the client is the the same as
	  // the last modified date of the requested file. If so, return 304 header
	  // and exit.
	  // check if the Etag sent by the client is the same as the Etag of the
	  // requested file. If so, return 304 header and exit.
	  if (!$this->skip_cache) {

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{

// 			error_log("Storage::checkCache HTTP_IF_NONE_MATCH: ".$_SERVER['HTTP_IF_NONE_MATCH'],4);

			if (strcmp(str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])),$etag)==0)
			{
				header("HTTP/1.1 304 Not Modified");
				header("Expires: $expire");

				header("Cache-Control: public, must-revalidate");
// 				header("Pragma: ".$this->headers["etag"]);

				header("Last-Modified: $last_modified");
				header("ETag: $etag");

				exit;
			}
		}

		else if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
// 			error_log("Storage::checkCache HTTP_IF_MODIFIED_SINCE: ".$_SERVER['HTTP_IF_NONE_MATCH'],4);

			if (strcmp($_SERVER['HTTP_IF_MODIFIED_SINCE'],$last_modified)==0)
			{
				header('HTTP/1.1 304 Not Modified');
				header("Expires: $expire");

				header("Cache-Control: public, must-revalidate");
// 				header("Pragma: $etag");

				header("Last-Modified: $last_modified");
				header("ETag: $etag");

				exit;
			}
		}

	  }

	  $this->headers["etag"]=$etag;
	  $this->headers["expire"]=$expire;
	  $this->headers["last_modified"]=$last_modified;
	  return false;
  }
  protected function sendResponse()
  {

	  $mime = "application/octetsream";
	  if (isset($this->row["mime"])) {
		  $mime = $this->row["mime"];

	  }

	  header("Content-Type: $mime");
	  header("Last-Modified: ".$this->headers["last_modified"]);
	  header('ETag: "'.$this->headers["etag"].'"');


	  header("Cache-Control: must-revalidate");
// 	  header("Pragma: ".$this->headers["etag"]);

	  header("Expires: ".$this->headers["expire"]);
	  header("Content-Length: " . $this->row["size"]);

	  $filename = $this->headers["etag"];
	  if (isset($this->row["filename"])) {
		  $filename = $this->row["filename"];
	  }

	if (strcmp($this->disposition,"attachment")==0) {
	  header("Content-Disposition: ".$this->disposition."; filename=$filename");
	}
	  header("Content-Transfer-Encoding: binary");


	  print($this->row[$this->blob_field]);

	  exit;
  }
  protected function sendError(Exception $e)
  {

      // send the right headers
      header("Content-Type: text/html");
      header("Content-Length: ".strlen($e->getMessage()));
      echo $e->getMessage();

      exit;
  }
  protected function sendNotFound()
  {

      header("HTTP/1.0 404 Not Found");
      exit;
	
  }
}
?>