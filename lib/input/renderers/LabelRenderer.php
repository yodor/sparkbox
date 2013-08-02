<?php
include_once("lib/components/Component.php");
include_once("lib/input/renderers/ILabelRenderer.php");
include_once("lib/input/renderers/IErrorRenderer.php");


class LabelRenderer extends Component implements ILabelRenderer, IErrorRenderer
{

  protected $field = NULL;

  public $error_render_mode = IErrorRenderer::MODE_TOOLTIP;


  public function __construct()
  {
    $this->component_class = "InputLabel";
    
  }
  public function renderLabel(InputField $field, $render_index=-1)
  {

      $this->field = $field;
      $this->render_index = $render_index;

      $this->startRender();

      $this->renderImpl();

      $this->finishRender();
  }
  
  public function startRender()
  {
      $this->processErrorAttributes();
      
      parent::startRender();
      echo "<label>";
  }
  
  public function renderImpl()
  {
      echo $this->field->getLabel();

      $star = "";

      if ($this->field->getForm() && $this->field->getForm()->star_required){
	      $star = ($this->field->isRequired() || $this->field->isScriptRequired()) ? "<span class=required>*</span>" : "";
      }

      echo $star;

      if ($this->error_render_mode == IErrorRenderer::MODE_SPAN){
	echo "<small class='error_details'>";
	echo $this->field->getError();
	echo "</small>";
      }
      
  }
  public function finishRender()
  {
    echo "</label>";
    parent::finishRender();
  }
  
  public function processErrorAttributes()
  {

      if ($this->field->haveError()) {
	
	  $field_error = $this->field->getError();

	  if (is_array($field_error)) {
	    // 	$field_error = implode(";", $field_error);
	    $field_error = tr("Some elements of this collection have errors");

	  }
	  if (strlen($field_error)>0) {
	    $this->setAttribute("error", 1);

	    if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
	      $this->setAttribute("tooltip",$field_error);
	    }

	  }
      }
      else {
	  $this->setAttribute("error", false);
      }

  }


}
?>