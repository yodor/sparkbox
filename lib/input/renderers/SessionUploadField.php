<?php
include_once("lib/input/renderers/InputRenderer.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/input/renderers/IArrayFieldRenderer.php");

abstract class SessionUploadField extends InputRenderer implements IArrayFieldRenderer, IHeadRenderer
{
  protected $ajax_handler = NULL;
  
  public function __construct()
  {
      parent::__construct();
      $this->setFieldAttribute("type","file");

      $this->ajax_handler = new UploadControlAjaxHandler();
      RequestController::addAjaxHandler($this->ajax_handler);
  }
  
  public function assignUploadHandler(UploadControlAjaxHandler $handler)
  {
      $this->ajax_handler = $handler;
  }
  
  public function renderScript()
  {
      echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/jqplugins/jquery.form.js'></script>";
      echo "\n\n";
		
      echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/SessionUpload.js'></script>";
      echo "\n";
  }
  
  public function renderStyle()
  {
      
      echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/UploadControl.css' type='text/css' >";
      echo "\n";
       
  }
  
  public abstract function renderArrayContents();
  
  public function renderControls()
  {
	echo "<div class='Controls' >";
	  StyledButton::DefaultButton()->drawButton("Browse","","browse");

	  $attr = $this->prepareFieldAttributes();

	  echo "<input $attr>";
	
	  echo "<div class='progress'>";
	    echo "<div class='bar'></div>";
	    echo "<div class='percent'>0%</div>";
	  echo "</div>";
	echo "</div>";
  
  }
  
  public function renderElementSource()
  {
      //
  }

  public function startRender()
  {
      $max_slots = $this->field->getProcessor()->max_slots;
      $this->setFieldAttribute("max_slots", $max_slots);
      
      if ($this->ajax_handler instanceof UploadControlAjaxHandler) {
	  $this->setAttribute("handler_command", $this->ajax_handler->getCommandName());
      }
      else {
	  $this->setAttribute("handler_command", "null");
      }
      
      parent::startRender();
  }
  public function renderDetails()
  {
      $max_slots = $this->field->getProcessor()->max_slots;
      
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
	  echo "<span field='max_slots'><label>Available Slots: </label>".$max_slots."</span>";

	echo "</div>";
	
      echo "</div>";
  }
  public function renderImpl()
  {

      
      
      echo "<div class='FieldElements'>";

  
      
	$this->renderDetails();
	echo "\n";
	$this->renderControls();
	echo "\n";
	$this->renderArrayContents();
	echo "\n";
?>
<script type='text/javascript'>
addLoadEvent(function(){
  var upload_control = new SessionUpload();
  upload_control.attachWith("<?php echo $this->field->getName();?>");
  
});
</script>
<?php
      echo "</div>";

     
  }
  
}
?>