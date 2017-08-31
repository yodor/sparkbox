<?php
include_once("lib/handlers/JSONRequestHandler.php");
include_once("lib/components/renderers/IPhotoRenderer.php");

include_once("lib/input/validators/ImageUploadValidator.php");
include_once("lib/input/validators/FileUploadValidator.php");

include_once("lib/input/renderers/InputRenderer.php");

class UploadControlAjaxHandler extends JSONRequestHandler implements IPhotoRenderer
{

    const VALIDATOR_FILE = "file";
    const VALIDATOR_IMAGE = "image";

    protected $validator = NULL;

    protected $field_name = NULL;

    //   IPhotoRenderer
    protected $width = -1;
    protected $height = -1;
    protected $render_mode = IPhotoRenderer::RENDER_CROP;

    public function setThumbnailSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setRenderMode($mode) 
    {
        $this->render_mode = $mode;
    }

    public function getRenderMode() 
    {
        return $this->render_mode;
    }

    public function getThumbnailWidth() 
    {
        return $this->width;
    }
        
    public function getThumbnailHeight() 
    {
        return $this->height;
    }

    public function __construct()
    {
        parent::__construct("upload_control");

        //previews of uploaded images are thumbnails with autofit to width 64
        $this->setThumbnailSize(64,-1);
        
    }

    protected function parseParams()
    {
        parent::parseParams();
        if (!isset($_GET["field_name"])) throw new Exception("Field name not passed");
        $field_name = $_GET["field_name"];
        $this->field_name = str_replace("[]","", $field_name);
        
    }

    public function createUploadContents(StorageObject &$value_current, $field_name)
    {
    
	//TODO:prepare other style contents for files. render files as alternating rows icon, filename , type, size, X
	
	debug("UploadControlAjaxHandler::createUploadContents() ...");
	
	$filename = $value_current->getFileName();
	
	$mime = $value_current->getMIME();
	
	$uid = $value_current->getUID();
        
        debug("UploadControlAjaxHandler::createUploadContents() UID:$uid filename:$filename mime:$mime");
        
        
        //construct image data in row and pass to ImageResizer to create a temporary thumbnail of the uploaded image.
        $row = array();
        
        $row["mime"] = $value_current->getMIME();
        
        //data is null during ajax upload. image data can be retreived from tempName file.
        if ($value_current->getData()) {
            $row["photo"] = $value_current->getData();
        }
        else {
            $row["photo"] = file_get_contents($value_current->getTempName());
        }

        //temporary resize for base64_encode returned in ajax response
        ImageResizer::$max_width = $this->getThumbnailWidth();
        ImageResizer::$max_height = $this->getThumbnailHeight();
        ImageResizer::crop($row);
        
        //gc_collect_cycles();
        
	ob_start();
	if ($value_current instanceof ImageStorageObject) {
	  $image_data = "data:$mime;base64,".base64_encode($row["photo"]);
	  unset($row);
	  
	  $itemID = $value_current->itemID;
	  $itemClass = $value_current->itemClass;
	  echo "<div class='Element' tooltip='$filename' itemID='$itemID' itemClass='$itemClass'>";
	    echo "<a target='_itemGallery' href='".SITE_ROOT."storage.php?cmd=gallery_photo&class=$itemClass&id=$itemID'><img class='thumbnail' src='$image_data'></a>";
	    echo "<span class='remove_button' action='Remove'>X</span>";
	    echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
	  echo "</div>";
	}
	else if ($value_current instanceof FileStorageObject) {
	  $arr = explode("/", $mime, 2);
	  $first = $arr[0];
	  echo "<div class='Element' tooltip='$filename'>";
	    echo "<span class='thumbnail'><img src='".SITE_ROOT."lib/images/mimetypes/generic.png'></span>";
	    echo "<div class='details'>";
	    echo "<span class='filename'><label>$filename</label></span>";
	    echo "<span class='filesize'><label>".file_size($value_current->getLength())."</label></span>";
	    echo "</div>";
	    echo "<span class='remove_button' action='Remove'>X</span>";
	    echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
	  echo "</div>";
	}
	$html = ob_get_contents();
        
	ob_end_clean();

	return array(
	  "name"=>$filename, 
	  "uid"=>$uid,
	  "mime"=>$mime,
	  "html"=>$html,
	);
    }
    
    public function createValidator($validator_requested)
    {
	$validator = null;

	if (strcmp($validator_requested, UploadControlAjaxHandler::VALIDATOR_IMAGE)==0) {
	  $validator = new ImageUploadValidator();
	  //turn off resizing during ajax calls. resizing will be done on the final submit of form
	  $validator->setResizeEnabled(false);
	  //$validator->setResizedSize($this->width, $this->height);
	}
	else if (strcmp($validator_requested, UploadControlAjaxHandler::VALIDATOR_FILE)==0) {
	  $validator = new FileUploadValidator();
	  
	}
	else {
	  throw new Exception("Unrecognized validator requested");
	}

	debug("UploadControlAjaxHandler::createValidator() Result validator = ".get_class($validator));
	
	return $validator;
	
    }
    
    protected function _upload(JSONResponse $resp)
    {

        debug("UploadControlAjaxHandler::_upload() ...");
        $validator_requested = $_GET["validator"];

        debug("UploadControlAjaxHandler::_upload() creating input validator");
        $validator = $this->createValidator($validator_requested);
        
        
        $input = new InputField($this->field_name, "Upload Control", 1);

        $input->setValidator($validator);
        $input->setProcessor(new UploadDataInputProcessor());

        debug("UploadControlAjaxHandler::_upload() loading POST data");
        $input->loadPostData($_POST);

        debug("UploadControlAjaxHandler::_upload() validating input data");
        $input->validate();


        $upload_object = $input->getValue();

        //TODO:multiple uploaded files can be processed?
        $num_files = 0;

        if ($input->haveError()) {
            throw new Exception("There was error processing file <B>".$upload_object->getFileName()."</b> Error: ".$input->getError());
        }

        $this->assignUploadObjects($resp, $upload_object);
        
        debug("UploadControlAjaxHandler::_upload() finished");
    }   
  
    protected function assignUploadObjects(JSONResponse $resp, &$upload_object)
    {
        debug("UploadControlAjaxHandler::assignUploadObjects() ...");
        
        $resp->objects[] = $this->createUploadContents($upload_object, $this->field_name);
        
        //storage the original file in the session array
        $file_storage = new FileStorageObject();
        $file_storage->setUploadStatus(UPLOAD_ERR_OK);
        
        //do not set tempName as it is valid only during current request
        //$file_storage->setTempName($upload_object->getTempName());
        $file_storage->setTimestamp($upload_object->getTimestamp());
        $file_storage->setUID($upload_object->getUID());

        //assign original contents of the uploaded file. it will be resized depending on defaults on confirm submit/validate of input
        $file_storage->setData(file_get_contents($upload_object->getTempName()));
        $file_storage->setFilename($upload_object->getFileName());
        $file_storage->setMIME($upload_object->getMIME());
                    
        $_SESSION["upload_control"][$this->field_name][(string)$upload_object->getUID()] = serialize($file_storage);
        debug("UploadControlAjaxHandler::assignUploadObjects() | Session storing file UID: ".$upload_object->getUID()." for field['".$this->field_name."']");

    //       $num_files++;

        $resp->object_count = 1;
    }
  
    protected function _remove(JSONResponse $resp)
    {
        debug("UploadControlAjaxHandler::_remove() ...");
        
        if (!isset($_GET["uid"])) throw new Exception("UID not passed");

        $uid = (string)$_GET["uid"];
        if (strlen($uid)>50) throw new Exception("UID maximum size reached");
        
        debug("UploadControlAjaxHandler::_remove() UID: ".$uid);

        if (isset($_SESSION["upload_control"][$this->field_name][$uid])) {
            debug("UploadControlAjaxHandler::_remove() Removing UID:'$uid' from session array");
            unset($_SESSION["upload_control"][$this->field_name][$uid]);
        }
        
        debug("UploadControlAjaxHandler::_remove() finished");
    }

}
?>
