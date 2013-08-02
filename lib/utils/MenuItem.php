<?php
include_once("lib/utils/MainMenu.php");

class MenuItem
{
    protected $href="";
    protected $title="";
    protected $icon="";
    
    protected $w=0;
    protected $h=0;
    
    protected $selected = false;
    protected $disabled = false;

    protected $parent_item = NULL;
    
    protected $target = "";

    protected $childNodes = array();

    public static $icon_path = "../images/admin/menu_icons/";
    
    public function __construct($title, $href="", $icon="")
    {
	$this->title=$title;
	$this->href=$href;
	$this->icon=$icon;

    }
    
    public function setDisabled($mode)
    {
	$this->disabled = (($mode>0) ?  true : false);
    }
    
    public function isDisabled()
    {
	return $this->disabled;
    }
    
    public function isSelected()
    {
	    return $this->selected;
    }
    
    public function setSelected($mode)
    {
	    $this->selected = (($mode>0) ?  true : false);
    }

    public function setTarget($target)
    {
	$this->target = $target;
    }

    public function getTarget()
    {
	return $this->target;
    }

    public function clearChildNodes()
    {
	$this->childNodes = array();
    }

    public function addMenuItem(MenuItem $m)
    {
	$this->childNodes[] = $m;
	$m->setParent($this);

    }

    public function setParent(MenuItem $m)
    {
	$this->parent_item = $m;
    }

    public function getParent()
    {
	return $this->parent_item;
    }
    
    public function getSubmenu()
    {
	return $this->childNodes;
    }

    public function getTitle()
    {
	return $this->title;
    }
    
    public function getHref()
    {
	return $this->href;
    }
    
    public function setHref($href)
    {
	$this->href = $href;
    }
    
    public function getIcon()
    {
	return $this->icon;
    }

    public function getWidth()
    {
	return $this->w;
    }
    
    public function getHeight()
    {
	return $this->h;
    }
    
    public function setSize($width, $height)
    {
	$this->w = $width;
	$this->h = $height;
    }
}
?>