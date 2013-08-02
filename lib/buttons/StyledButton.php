<?php
include_once ("lib/components/Component.php");


class StyledButton extends Component implements IHeadRenderer
{


    public $image = "";
    public $image_align = -1;


    protected $text = "";
    protected $href = "";
    protected $button_name = "";


    const TYPE_LINK = 1;
    const TYPE_SUBMIT = 2;
    const TYPE_RESET = 3;
    const TYPE_BUTTON = 4;

    const ALIGN_LEFT = 1;
    const ALIGN_RIGHT = 2;

    protected $button_type;

    protected static $default_button = NULL;
    protected static $default_class = "";

    public static function DefaultButton()
    {
	if (self::$default_button) {
	  $btn_clone = clone self::$default_button;
	  return $btn_clone;
	}
	include_once ("lib/buttons/DefaultButton.php");
	$d = new DefaultButton();
	$d->setClassName(self::$default_class);

	return $d;
    }
    public function renderScript()
    {
    
    }
    public function renderStyle()
    {
	echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/DefaultButton.css' type='text/css' >";
	echo "\n";
    }
    public static function setDefaultButton(StyledButton $button)
    {
	self::$default_button = $button;
    }
    
    public static function setDefaultClass($css_class) 
    {
	self::$default_class = $css_class;
    }
    
    public function setButtonType($type)
    {
	$this->button_type = $type;
    }
    
    public function __construct($type=StyledButton::TYPE_LINK)
    {
	parent::__construct();
	
	$this->setType($type);
    }
    
    public function setType($type) 
    {
	$this->button_type = $type;
	switch ($this->button_type) {
		case StyledButton::TYPE_LINK:
			  $this->setAttribute("type", "button");
			  break;
		case StyledButton::TYPE_SUBMIT:
			  $this->setAttribute("type", "submit");
			  break;
		case StyledButton::TYPE_RESET:
			  $this->setAttribute("type", "reset");
			  break;
		case StyledButton::TYPE_BUTTON:
			  $this->setAttribute("type", "button");
			  break;
		default:
			  $this->setAttribute("type", "button");
			  break;
	}

    }
    
    public function getText() {
	return $this->text;
    }
    
    public function setText($text)
    {
	$this->text = $text;
    }
    
    public function setValue($value)
    {
	$this->value = $value;
	$this->setAttribute("value",$value);

    }
    
    public function getValue()
    {
	return $this->value;
    }
    
    public function getHref() 
    {
	return $this->href;
    }
    
    public function setHref($href)
    {
	$this->href = $href;


    }
    
    public function setName($name)
    {
	$this->button_name = $name;
	$this->setAttribute("name", $name);

    }
    
    public function getName()
    {
	return $this->button_name;
    }

    public function startRender()
    {

	if (strlen($this->href)>0) {
	    if ($this->button_type == StyledButton::TYPE_LINK) {
	      $this->setAttribute("href", $this->href);
	    }
	    else {
	      
	      if (strpos($this->href, "javascript:")===0) {
		    $this->setAttribute("onClick", $this->href);
	      }
	      else {
		    $this->setAttribute("onClick","javascript:window.location.href='{$this->href}'");
	      }
	    }
	}

	$attrs = $this->prepareAttributes();


	if ($this->button_type == StyledButton::TYPE_LINK) {
	  echo "<a $attrs>";
	}
	else {
	  echo "<button $attrs>";
	}

    }
    public function finishRender()
    {
	if ($this->button_type == StyledButton::TYPE_LINK) {
	  echo "</a>";
	}
	else {
	  echo "</button>";
	}

    }
    
    public function renderImpl()
    {

	if ($this->image_align==StyledButton::ALIGN_LEFT) {
	    if (strlen($this->image)>0){
		    echo $this->image;
	    }
	}

	echo tr($this->text);

	if ($this->image_align==StyledButton::ALIGN_RIGHT) {
	    if (strlen($this->image)>0){
		    echo $this->image;
	    }
	}
    }


    public function drawButton($text="Button", $href="#", $value="button")
    {
	$this->setType(StyledButton::TYPE_BUTTON);
	$this->setText($text);
	if (strlen($href)>0) {
	  $this->setHref($href);
	}
	$this->setValue($value);
	$this->render();
    }


    public function drawSubmit($text="Submit", $name="submit_item", $value="submit_item")
    {
	$this->setType(StyledButton::TYPE_SUBMIT);
	$this->setText($text);
	$this->setAttribute("value", $value);
	$this->setAttribute("name", $name);
	$this->render();

    }

    public function enableIcon($img, $align=StyledButton::ALIGN_LEFT)
    {
	$this->image_align = $align;
	$this->image = "<img src='$img'>";
    }
}

?>