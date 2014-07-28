<?php
include_once ("lib/components/renderers/IRenderer.php");
include_once ("lib/components/renderers/IHeadRenderer.php");
include_once ("lib/components/renderers/IFinalRenderer.php");
include_once ("lib/pages/SitePage.php");

abstract class Component implements IRenderer
{
  protected $className = "";
  protected $attributes = array();
  protected $style = array();

  protected $parent_component=false;
  protected $caption = "";

  protected $tooltip_text = "";

  protected $json_attributes = array("tooltip");

  protected $component_class = "";

  public $render_tooltip = true;
  
  
  protected $name = "";
  
  public function __construct()
  {
      $this->component_class = get_class($this);

      if (SitePage::getInstance() instanceof SimplePage) {
	  if ($this instanceof IFinalRenderer) {
	    SitePage::getInstance()->addFinalComponent($this);
	  }
	  if ($this instanceof IHeadRenderer) {
	    SitePage::getInstance()->addHeadComponent($this);
	  }
      }
  }
  
  public function getHeadClass()
  {
      return $this->component_class;
  }
  
  protected abstract function renderImpl();

  public function startRender()
  {
      $attrs = $this->prepareAttributes();
      echo "<div $attrs>";
  }

  public function finishRender()
  {
      echo "</div>";
  }

  public function render()
  {
      if ($this instanceof IFinalRenderer) throw new Exception("Trying to render final component");
      
      try {
	  $this->startRender();
	  $this->renderImpl();
	  $this->finishRender();
      }
      catch (Exception $e) {
	  echo $e->getMessage();
      }
  }
  
  
  
  public function setName($name)
  {
      $this->setAttribute("name" , $name);
      $this->name = $name;
  }
  
  public function getName()
  {
      return $this->name;
  }
  public function setParent(Component $parent)
  {
	$this->parent_component = $parent;
  }
  public function getParent()
  {
	return $this->parent_component;
  }
  public function getCaption()
  {
	return $this->caption;
  }
  public function setCaption($caption)
  {
	$this->caption = $caption;
  }
  public function getTooltipText()
  {
	  return $this->tooltip_text;
  }
  public function setTooltipText($text)
  {
	  $this->tooltip_text = $text;
	  $this->attributes["tooltip"]=$text;
  }
  public function getAttributeArray()
  {
	  return $this->attributes;
  }
  public function getStyleArray()
  {
	  return $this->style;
  }
  public function getClassName()
  {
	  return $this->className;
  }
  public function setClassName($className)
  {
	  $this->className=$className;
  }
  public function addClassName($className)
  {
	$this->className.=" ".$className;
  }
  public function setAttribute($name, $value)
  {
	  $this->attributes[$name]=$value;
	  return $this;
  }
  public function clearAttribute($name)
  {
	  if (isset($this->attributes[$name])) {
		unset($this->attributes[$name]);
	  }
  }
  public function getAttribute($name)
  {
	  return $this->attributes[$name];
  }
  public function setStyleAttribute($name, $value)
  {
	  $this->style[$name]=$value;
	  return $this;
  }
  public function getStyleAttribute($name)
  {
	  return $this->style[$name];
  }

  public function getAttributesText($src_attributes=null)
  {
	  if (!$src_attributes)$src_attributes = $this->attributes;
	  
	  $attributes = array();

	  foreach ($src_attributes as $attribute_name=>$value) {
	  
		  if (!$this->render_tooltip && strcmp($attribute_name,"tooltip")==0)continue;
	  

		  if (is_null($value) || strlen($value)<1) {
			
			$attributes[] = $attribute_name;
			
		  }
		  else {
		  
			$attribute_value = attributeValue($value);
			
			if (in_array($attribute_name, $this->json_attributes)) {
			  
			  $attributes[] = $attribute_name."=".json_string($attribute_value);
			}
			else {
			  
			  $attributes[] = $attribute_name."='".$attribute_value."'";
			}
		  }
	  }

	  if (count($attributes)>0) {
		return implode(" ", $attributes);


	  }

	  return "";

  }
  public function getStyleText()
  {

	  $styles = array();

	  foreach ($this->style as $style_name=>$value) {
		  if (strlen($value)<1)continue;

		  $styles[]=$style_name.":".$value;
	  }

	  if (count($styles)>0) {
		$style_text = implode(";", $styles);

		return " style='$style_text' ";
	  }
	  else {
		return "";
	  }

  }
  public function prepareAttributes()
  {
	  $attrs = "";
	  $class_names = trim($this->component_class." ".$this->className);
	  
	  
	  if (strlen($class_names)>0) {
	    $attrs.=" class='$class_names' ";
	  }
	  
	  $attrs.=$this->getAttributesText();
	  $attrs.=$this->getStyleText();

	  return $attrs;
  }
  public function appendAttributes($attributes)
  {
	foreach($attributes as $name=>$value) {
	    $this->attributes[$name] = $value;
	}
  }
//   public function renderHtml()
//   {
//     $buffer = "<div>Error</div>";
//     
//     register_shutdown_function(function() {
// 
// 	  $err = error_get_last();
// 
// 	  if (is_array($err)) {
// 	  
// 	      ob_end_clean();
// 	      
// 	      
// 
// 	  }
// 	
//     });
//     ob_start();
//     $this->render();
//     $buffer = ob_get_contents();
//     ob_end_clean();
//     return $buffer;
//   }
}

?>