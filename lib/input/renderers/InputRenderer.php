<?php
include_once("lib/components/Component.php");
include_once("lib/beans/IDataSource.php");
include_once("lib/input/renderers/IFieldRenderer.php");
include_once("lib/input/renderers/IErrorRenderer.php");

abstract class InputRenderer extends Component
	implements IDataSource, IFieldRenderer, IErrorRenderer

{
  
  
  public $error_render_mode = IErrorRenderer::MODE_TOOLTIP;
  
  public static $value_na = "-";
  
  protected $field = NULL;
  


  protected $data_bean = NULL;
  protected $data_filter = "";
  protected $data_fields = " * ";
  
  public $list_key=false;
  public $list_label=false;


  public $addon_content = "";


  protected $freetext_value = false;

  protected $field_attributes = array();

  

  public function __construct()
  {
      
      
      parent::__construct();

      $this->component_class = "InputField";
      
      $this->setClassName(get_class($this));
      
      $this->tooltip_text = "";
      
  }


  public function setFieldAttribute($name, $value)
  {
      $this->field_attributes[$name]=$value;
      return $this;
  }
  public function getFieldAttribute($name)
  {
      return $this->field_attributes[$name];
  }
  
  
  public function enableFreetextInput($src_id)
  {
      $this->freetext_value = $src_id;
  }
  public function getFreetextInput()
  {
      return $this->freetext_value;
  }

  public function setFilter($filter)
  {
      $this->data_filter = $filter;
  }
  
  public function setSource(IDataBean $data_bean)
  {
      $this->data_bean=$data_bean;
  }

  public function getSource()
  {
      return $this->data_bean;
  }
  

  public function processErrorAttributes()
  {

      $field_error = $this->field->getError();

      if (is_array($field_error)) $field_error = implode(";", $field_error);

      if (strlen($field_error)>0) {
      
	  if ($this->error_render_mode == IErrorRenderer::MODE_TOOLTIP) {
	    $this->attributes["tooltip"] = $field_error;
	  }
	  $this->attributes["error"] = 1;
      }
      else {
	  $this->attributes["error"] = false;
      }

      
  }


  public function prepareFieldAttributes()
  {
      if (!$this->field->isEditable()) {
	    $this->setFieldAttribute("disabled","true");
      }
      return $this->getAttributesText($this->field_attributes);
      
  }
  public function getField()
  {
      return $this->field;
  }
  public function setField(InputField $field)
  {
      $this->field = $field;

      
      $this->setFieldAttribute("name", $field->getName());
      
      
      //access attributes directly. allow sub components to override setAttribute
      $this->attributes["field"] = $field->getName();
      $this->attributes["tooltip"] = $this->tooltip_text;
      
      $this->processErrorAttributes();
      
      
      $field_error = $field->getError();

      if (is_array($field_error)) $field_error = implode(";", $field_error);

      if (strlen($field_error)>0) {
      
	  if ($this->error_render_mode == IErrorRenderer::MODE_SPAN) {
	    echo "<span class='error_detail'>";
	    echo $field_error;
	    echo "</span>";
	  }

      }
  }
  
  public function renderField(InputField $field)
  {
      $this->setField($field);
      
      $this->startRender();
      $this->renderImpl();
      $this->finishRender();
  }
  
  public function renderValue(InputField $field, $render_index=-1)
  {
      $this->field=$field;

      $this->component_class = "InputValue";
      
      $this->startRender();
      $this->renderValueImpl();
      $this->finishRender();

  }

  public function finishRender()
  {
      
     
      $user_data = $this->field->getUserData();
      if (strlen($user_data)>0) {
	  echo "<div class='UserData'>";
	  echo $user_data;
	  echo "</div>";
      }
      
      
      if ($this->addon_content) {
	echo "<div class='addon_content'>";
	echo $this->addon_content;
	echo "</div>";
      }
      
      parent::finishRender();
  }



  public function renderValueImpl()
  {
      $field_value = $this->field->getValue();

      if (strlen($field_value)<1) echo self::$value_na;

      else {

	  $link_field = $this->field->getLinkField();
	  
	  if ($link_field instanceof InputField) {
	      $link_value = $link_field->getValue();

	      if (strcmp($this->field->getRenderer()->getFreetextInput(), $field_value)===0) {
		    echo $field_value." - ".$link_value;
	      }
	      else {
		    echo $field_value;
	      }
	  }
	  else {
		  $field_value = $this->field->getValue();
		  
		  $field_value=htmlentities(mysql_real_unescape_string($field_value),ENT_QUOTES,"UTF-8");
		  $field_value = str_replace("\n", "<BR>", $field_value);
		  echo $field_value;
	  }
      }


  }
}
?>