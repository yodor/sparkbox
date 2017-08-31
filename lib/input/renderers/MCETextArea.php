<?php
include_once("lib/input/renderers/InputRenderer.php");
include_once("lib/panels/MCEImageBrowserDialog.php");

class MCETextArea extends InputRenderer implements IHeadRenderer 
{

//   protected $image_browser = NULL;

  protected static $image_browser = NULL;

  public function __construct()
  {

      parent::__construct();

      //force single instance of the dialog to all MCETextAreas to prevent double session upload
      if (self::$image_browser === NULL) {
        self::$image_browser = new MCEImageBrowserDialog();
      }

      
  }
  public function getHeadClass()
  {
      return "MCETextArea";
  }
  public function renderStyle()
  {
      echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/MCETextArea.css' type='text/css' >";
      echo "\n";
  }
  public function renderScript()
  {

      echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/MCETextArea.js'></script>";
      echo "\n";
      
      echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/tiny_mce/jquery.tinymce.min.js'></script>";
      echo "\n";
      
   

  }
  public function setAttribute($name, $value)
  {
      $this->setFieldAttribute($name,$value);
      self::$image_browser->setAttribute($name, $value);
  }
  public function getImageBrowser()
  {
      return self::$image_browser;

  }
  public function renderImpl()
  {

	$field_attrs = $this->prepareFieldAttributes();
	 
	echo "<textarea class='MCETextArea' $field_attrs>";
	
	$field_value = $this->field->getValue();

	$field_value = htmlentities(mysql_real_unescape_string($field_value),ENT_QUOTES,"UTF-8");
	echo $field_value;
	
	echo "</textarea>";
	?>
	<script type='text/javascript'>
	addLoadEvent(function(){
	  var mce = new MCETextArea();
	  mce.attachWith("<?php echo $this->field->getName();?>");
	});
	</script>
	<?php

	
	
  }

  public function renderValueImpl()
  {
      $field_value = $this->field->getValue();

      if (strlen($field_value)>0) {
	    $field_value=htmlentities(mysql_real_unescape_string($field_value),ENT_QUOTES,"UTF-8");
	    $field_value = str_replace("\n", "<BR>",$field_value);
	    echo $field_value;
      }
      else {
	    echo "-";
      }
  }

}
?>
