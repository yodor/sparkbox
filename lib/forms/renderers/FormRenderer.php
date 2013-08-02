<?php
include_once("lib/components/Component.php");
include_once("lib/forms/renderers/IFormRenderer.php");
include_once("lib/components/InputComponent.php");


class FormRenderer extends Component implements IFormRenderer, IHeadRenderer
{
  protected $form = NULL;
  protected $submit_button = NULL;
  protected $render_field_callback = NULL;

  const FIELD_HBOX = "HBox";
  const FIELD_VBOX = "VBox";
  
  protected $field_layout = FormRenderer::FIELD_VBOX;

  protected $buttons = array();

  public $contains_upload = false;

  protected $field_renderer = NULL;
  
  public function __construct()
  {
      parent::__construct();
      $this->attributes["method"]="post";
      $this->attributes["enctype"]="multipart/form-data";
      

      $this->submit_button = StyledButton::DefaultButton();
      $this->submit_button->setName("submit_item");
      $this->submit_button->setText("Submit Form");
      $this->submit_button->setValue("submit");

      $this->submit_button->setType(StyledButton::TYPE_SUBMIT);

      $this->field_renderer = new InputComponent();

      $this->setFieldLayout(FormRenderer::FIELD_HBOX);

  }
  public function renderScript()
  {}
  public function renderStyle()
  {
      echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/FormRenderer.css' type='text/css' >";
      echo "\n";
  }
  public function getFieldRenderer()
  {
      return $this->field_renderer;
  }
  public function addButton(StyledButton $b)
  {
      $this->buttons[$b->getText()]=$b;
  }
  public function getButton($text)
  {
      return $this->buttons[$text];
  }
  public function setFieldLayout($mode)
  {
      $this->field_layout = $mode;
      $this->attributes["field_layout"] = $this->field_layout;
  }
  public function setRenderFieldCallback($fname)
  {
      $this->render_field_callback = $fname;
  }
  public function setForm(InputForm $form)
  {
      $this->form = $form;
      $this->form->setRenderer($this);

  }
  public function startRender()
  {
      $attrs = $this->prepareAttributes();
      echo "<form $attrs>";
      if ($this->contains_upload) {
	  echo "<input type=hidden name='MAX_FILE_SIZE' value='".UPLOAD_MAX_FILESIZE."'>";
      }
      
      
  }

  public function finishRender()
  {
      
      
      echo "</form>";
  }

  public function renderImpl()
  {
      $fields = $this->form->getFields();
      foreach($fields as $field_name=>$field) {
	  $this->renderField($field);
      }
  }

  public function getSubmitName(InputForm $form)
  {
      return $this->submit_button->getName();
  }

  public function getSubmitButton()
  {
      return $this->submit_button;
  }

  public function renderForm(InputForm $form)
  {
      $this->form=$form;

      $this->startRender();

      $this->renderImpl();

      $this->renderSubmitLine($this->form);

      echo "<div class=clear></div>";

      $this->finishRender();

  }
  public function renderSubmitLine(InputForm $form)
  {
      echo "<div class='SubmitLine'>";
	    
	echo "<div class='TextSpace'>";
	echo "</div>";
	
	echo "<div class='Buttons'>";
	$this->submit_button->render();

	foreach($this->buttons as $href=>$btn) {
	    $btn->render();

	}
	echo "</div>";
	    
      echo "</div>";
  }


  public function renderField(InputField $field)
  {
      if ($field->getLinkMode()) return;

      $callback_rendered = false;
      if ($this->render_field_callback) {
	  if (is_callable($this->render_field_callback)) {
	    $callback_rendered = call_user_func($this->render_field_callback, $field, $this);
	  }
	  else {
	    //TODO: Check if exception throwing is more appropriate here
	    debug("callback set but callback render function not callable");
	    
	  }
      }
      if (!$callback_rendered) {
	  $this->field_renderer->setField($field);
	  $this->field_renderer->render();
      }

  }


}

?>