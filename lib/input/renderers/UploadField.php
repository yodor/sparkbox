<?php
include_once("lib/input/renderers/InputRenderer.php");


abstract class UploadField extends InputRenderer implements IHeadRenderer
{
 

  public function __construct()
  {
      parent::__construct();
      $this->setFieldAttribute("type","file");

  }
  
  public function renderScript()
  {

      echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/PlainUpload.js'></script>";
      echo "\n";
  }
  
  public function renderStyle()
  {
      
      echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/UploadControl.css' type='text/css' >";
      echo "\n";
       
  }

  protected abstract function renderContents(StorageObject $storage_object);
  
  
  public function renderImpl()
  {
      $storage_object = $this->field->getValue();
      $field_name = $this->field->getName();

      echo "<div class='FieldElements'>";
      
      
      echo "<div class='Details'>";
	
	if (strlen($this->caption)>0) {
	  echo "<span class='Caption'>";
	  echo $this->caption;
	  echo "</span>";
	}
	
	echo "<div class='Limits'>";
	  echo "<span field='max_size'><label>UPLOAD_MAX_FILESIZE: </label>".file_size(UPLOAD_MAX_FILESIZE)."</span>";
	  echo "<span field='max_post_size'><label>POST_MAX_FILESIZE: </label>".file_size(POST_MAX_FILESIZE)."</span>";
	  echo "<span field='memory_limit'><label>MEMORY_LIMIT: </label>".file_size(MEMORY_LIMIT)."</span>";
	echo "</div>";
	

	
	echo "<span class='Filename'>";
	echo "</span>";
	
      echo "</div>";
      
      echo "<div class='Controls' >";
	  StyledButton::DefaultButton()->drawButton("Browse","","browse");

	  $attr = $this->prepareFieldAttributes();

	  echo "<input $attr>";

      echo "</div>";
	
      echo "<div class='Slots'><div class='Contents'>";

      if ($storage_object && $storage_object instanceof StorageObject) {
	$this->renderContents($storage_object);
      }
      
      echo "</div></div>";
      
      echo "</div>";

?>
<script type='text/javascript'>
addLoadEvent(function(){
  var upload_field = new PlainUpload();
  upload_field.attachWith("<?php echo $this->field->getName();?>");
  
});
</script>
<?php

  }
  
}
?>