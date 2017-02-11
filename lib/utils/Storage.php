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
  
  //network cache
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

			$this->checkCache($this->headers["etag"]);
			
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
			
                        $this->checkCache($this->headers["etag"]);
			
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

			$this->checkCache($this->headers["etag"]);
			
			ImageResizer::thumbnail($this->row, $size);
		}
		
		$this->sendResponse();
		
	  }
	  catch (ImageResizerException $e1) {
	    debug("ImageResizerException: ".$_SERVER["REQUEST_URI"]);
	    $this->sendError($e1);
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


	  $blob_field = $this->blob_field;

	  if (isset($_GET["blob_field"])) {
                $blob_field = $_GET["blob_field"];
	  }
	  else if (isset($_GET["bean_field"])) {
                $blob_field = $_GET["bean_field"];
	  }
	 
	  
	  $stypes = $this->bean->getStorageTypes();

	  if (!array_key_exists($blob_field, $stypes)) {
		throw new Exception("No such blob field found");
	  }

	  if (!isset($this->row[$blob_field])) {
		throw new Exception("No data for this blob field");
	  }
	  

	  
	  
	  $storage_object = @unserialize($this->row[$blob_field]);

	  if ($storage_object instanceof StorageObject) {
	  

		  $this->row = array();
		  //image resizer expects row["photo"]
		  $row_field = "photo";
		  if ($storage_object instanceof ImageStorageObject) {
                    $row_field = "photo";
		  }
		  else if ($storage_object instanceof FileStorageObject) {
                    $row_field="data";
		  }
                  $storage_object->deconstruct($this->row, $row_field, false);
		  
	  
		  $this->cache_hash.="|".$blob_field;
		  
		  
		  
	  }
	  else {
                
                //request received using blob_field but object is not of type storage object.
                if (isset($_GET["blob_field"]) || isset($_GET["bean_field"])) {
                    throw new Exception("Incorrect request received. Source data type is not StorageObject.");
                }
                //continue as object is tranacted to db as dbrow

		  
	  }

	  // set headers and etag
	  
	  $last_modified = gmdate("D, d M Y H:i:s T");

	  if (isset($this->row["date_upload"])) {
		$last_modified = gmdate("D, d M Y H:i:s T", strtotime($this->row["date_upload"]));
	  }
	  else if (isset($this->row["date_updated"])) {
		$last_modified = gmdate("D, d M Y H:i:s T", strtotime($this->row["date_updated"]));
	  }

	  //always keep one year ahead from request time
	  $expire = gmdate("D, d M Y H:i:s T", strtotime("+1 year", strtotime($last_modified)));

	  $etag = md5($this->cache_hash."-".$last_modified);


	  $this->headers["etag"]=$etag;
	  $this->headers["expire"]=$expire;
	  $this->headers["last_modified"]=$last_modified;
	  
          
          
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
  protected function checkCache($etag)
  {

        if ($this->skip_cache) return false;

        // error_log("Storage::checkCache last_modified: $last_modified | expire: $expire | etag: $etag",4);

        // check if the last modified date sent by the client is the the same as
        // the last modified date of the requested file. If so, return 304 header
        // and exit.
        // check if the Etag sent by the client is the same as the Etag of the
        // requested file. If so, return 304 header and exit.

        $send_cache = false;

//         if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
//         {
//             //error_log("Storage::checkCache HTTP_IF_MODIFIED_SINCE: ".$_SERVER['HTTP_IF_NONE_MATCH'],4);
// 
//             if (strcmp($_SERVER['HTTP_IF_MODIFIED_SINCE'],$last_modified)==0)
//             {
//                     $send_cache = true;
//             }
//             else {
//                     $send_cache = false;
//             }
//         }
        
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            //error_log("Storage::checkCache HTTP_IF_NONE_MATCH: ".$_SERVER['HTTP_IF_NONE_MATCH'],4);
            $pos = strpos($_SERVER['HTTP_IF_NONE_MATCH'], $etag);
            if ($pos!==FALSE)
            {
                $send_cache = true;
            }
            
        }

        if ($send_cache) {
            //cache response headers - use current datetime
            $last_modified = gmdate("D, d M Y H:i:s T");
            header("HTTP/1.1 304 Not Modified");
            header("Last-Modified: $last_modified");
            header("Cache-Control: no-cache, must-revalidate");
            //header("Pragma: ".$this->headers["etag"]);
            //header("ETag: $etag");
            exit;
        }

        
        //check disk cache - skip server side image resizing 
        $cache_folder = $this->getCacheFolder();  //"../spark_cache/{$this->className}/{$this->id}/"; //etag.bin
        if (!file_exists($cache_folder)) {
            return false;
        }
        $cache_file = $cache_folder."/".$this->getCacheFile();    
        if (!file_exists($cache_file)) {
            return false;
        }
        $handle = fopen($cache_file,'r');
        flock($handle, LOCK_SH);
        $this->row[$this->blob_field] = file_get_contents($cache_file);
        flock($handle, LOCK_UN);
        fclose($handle);

        $this->row["size"] = filesize($cache_file);
        $this->sendResponse(true);
        //exit
        
        return false;
	  
  }
  
  protected function getCacheFolder()
  {
        return "../spark_cache/".$this->className."/".$this->id."/";
  }
  
  protected function getCacheFile()
  {
        return $this->headers["etag"].".bin";
  }
  
  protected function sendResponse($is_cache_data=false)
  {

        $mime = "application/octetsream";
        if (isset($this->row["mime"])) {
            $mime = $this->row["mime"];
        }

        header("Content-Type: $mime");
        header("Last-Modified: ".$this->headers["last_modified"]);
        header("ETag: \"".$this->headers["etag"]."\"");

        header("Cache-Control: no-cache, must-revalidate");
        //header("Pragma: ".$this->headers["etag"]);

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

        if (!$is_cache_data) {
                    
            $cache_folder = $this->getCacheFolder();  
            if (!file_exists($cache_folder)) {
                mkdir($cache_folder, 0777, true);
            }
            
            $cache_file = $cache_folder."/".$this->getCacheFile();
            $handle = fopen($cache_file,'c');
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
            file_put_contents($cache_file, $this->row[$this->blob_field]);
            flock($handle, LOCK_UN);
            fclose($handle);

        }
        
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
