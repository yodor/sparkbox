<?php
include_once("lib/handlers/UploadControlAjaxHandler.php");
include_once("lib/components/renderers/IPhotoRenderer.php");

include_once("lib/input/validators/ImageUploadValidator.php");
include_once("lib/input/validators/FileUploadValidator.php");

include_once("lib/input/renderers/InputRenderer.php");

include_once("lib/utils/IStorageSection.php");
include_once("lib/beans/MCEImagesBean.php");

class ImageDimensionForm extends InputForm
{
  public function __construct()
  {
      parent::__construct();
      $modes = array("gallery_photo"=>"Full", "image_crop"=>"Crop", "image_thumb"=>"Thumbnail");
      
      $render_modes = new ArraySelector($modes, "id", "label");
      
      $field = InputFactory::CreateField(InputFactory::SELECT, "render_mode", "Render Mode", 0);
      $field->getRenderer()->setSource($render_modes);
      $field->getRenderer()->list_key="id";
      $field->getRenderer()->list_label="label";
      $field->getRenderer()->na_str="";
      
      $this->addField($field);
      
      $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "caption", "Caption", 0);
      $this->addField($field);
      
      $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "width", "Width", 0);
      $this->addField($field);
      
      $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "height", "Height", 0);
      $this->addField($field);
      
      $field = InputFactory::CreateField(InputFactory::CHECKBOX, "enable_popup", "Enable Popup", 0);
      $this->addField($field);
  }
}
class MCEImageBrowserAjaxHandler extends UploadControlAjaxHandler implements IStorageSection
{

  
  protected $field_name = NULL;
  
  protected $section_name = "global";
  protected $section_key = "";
  protected $ownerID = -1;
  protected $auth_context = NULL;

  public function __construct()
  {
      parent::__construct();
      $this->cmd = "mceImage";
      
      //do not require thumbnail. just create the imagestorage of the upload data
      $this->setThumbnailSize(-1,-1);
  }
  public function setSection($section_name, $section_key)
  {
    $this->section_name = $section_name;
    $this->section_key = $section_key;
  }
  public function setOwnerID($ownerID)
  {
    $this->ownerID = $ownerID;
  }
  public function setAuthenticator(Authenticator $auth)
  {
    $this->auth_context = $auth;
  }
  protected function parseParams()
  {
      parent::parseParams();
      


  }

    public function createUploadContents(StorageObject $object, $field_name)
    {
  
	
    }
    public function createUploadElement($imgrow)
    {
	$id = $imgrow["imageID"];
	

	ob_start();
	echo "<div class='Element' imageID=$id>";
	  echo "<div class='remove_button'>X</div>";
	  echo "<img class='image_contents' src='".SITE_ROOT."storage.php?cmd=image_crop&width=-1&height=128&class=MCEImagesBean&id=$id'>";
	echo "</div>";
	$html = ob_get_contents();
	ob_end_clean();
	
	return array(
	  "imageID"=>$id,
	  "html"=>$html,
	);
    }
   protected function _find(JSONResponse $resp)
   {
      debug("MCEImageBrowserAjaxHandler::_find | section_name='{$this->section_name}' AND section_key='{$this->section_key}'");
    
      $bean = new MCEImagesBean();
      $qry = $bean->getSelectQuery();
      $qry->where = " section='{$this->section_name}' AND section_key='{$this->section_key}'";
      
      if ($this->ownerID>0) {
	$qry->where.= " AND ownerID='{$this->ownerID}' ";
      
      }
      if (isset($_GET["imageID"])) {
	$imageID = (int)$_GET["imageID"];
	$qry->where.= " AND imageID='$imageID' ";
      }
      
      $qry->fields = " section, section_key, imageID, ownerID, auth_context ";
      
      
      $num_images = $bean->startSelectIterator($qry);
      
      $resp->objects = array();
      
      while ($bean->fetchNext($imgrow)) {
      
	$resp->objects[] = $this->createUploadElement($imgrow);
	
      }
    
      $resp->result_count = $num_images;
      
   }
   

    protected function assignUploadObjects(JSONResponse $resp, $upload_object)
    {
	debug("MCEImageBrowserAjaxHandler::assignUploadObjects");
	
	$bean = new MCEImagesBean();
	$bean_row = array();
	$bean_row["section"] = $this->section_name;
	if ($this->section_key) {
	  $bean_row["section_key"] = $this->section_key;
	}
	if ($this->ownerID>0) {
	  $bean_row["ownerID"] = $this->ownerID;
	}
	if ($this->auth_context) {
	  $bean_row["auth_context"] = get_class($this->auth_context);
	}

	$bean_row["photo"] = $upload_object->serializeDB();

	$imageID = $bean->insertRecord($bean_row);
	if ($imageID<1) throw new Exception("Unable to insert image object: ".$bean->getDB()->getError());
	
	debug("assignUploadObjects::stored to mce_images with imageID: $imageID");
	$bean_row["imageID"]=$imageID;

      $resp->objects = array();

      $resp->objects[] = $this->createUploadElement($bean_row);
	    
      $resp->result_count = 1;
  }

  
  protected function _remove(JSONResponse $resp)
  {

      if (!isset($_GET["imageID"])) throw new Exception("imageID not passed");

      $imageID = (int)$_GET["imageID"];

      $bean = new MCEImagesBean();

      $image_row = $bean->getByID($imageID);
      if ($image_row["auth_context"]) {
	$auth = $image_row["auth_context"];
	$ownerID = $image_row["ownerID"];
	
	@include_once("lib/auth/$auth.php");
	@include_once("class/auth/$auth.php");
	$class_loaded = class_exists($auth, false);
	if (!$class_loaded) throw new Exception("Unable to load the stored authenticator");
	
	$authenticated = $auth::checkAuthState();
	if (!$authenticated) throw new Exception("The requested resource is protected with authenticator");
	
	$lastID = $auth::lastID;
	
	if ($ownerID>0 && $lastID!=$ownerID) throw new Exception("The requested resource is protected with owner authenticator");
	
	
      }
      
      if (!$bean->deleteID($imageID)) throw new Exception("Unabel to delete: ".$bean->getDB()->getError());


  }
  protected function _renderDimensionDialog(JSONResponse $resp)
  {
       if (!isset($_GET["imageID"])) throw new Exception("imageID not passed");

      $imageID = (int)$_GET["imageID"];

      
      $form = new ImageDimensionForm();
      $frend = new FormRenderer();
      
      
      echo "<div class='ImageDimensionComponent'>";
      
	echo "<div class='preview'>";
	$img_href = SITE_ROOT."storage.php?cmd=image_crop&width=240&height=-1&id=$imageID&class=MCEImagesBean";
	echo "<img src='$img_href'>";
	echo "</div>";
	
	echo "<div class='dimension'>";
	$frend->setForm($form);
	$frend->render();
	echo "</div>";
      
      echo "</div>";
  }

}
?>