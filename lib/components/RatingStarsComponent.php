<?php

class RatingStarsComponent extends Component {
  
  
  protected static $script_initialized = false;
  public $min = 1;
  public $max = 5;

  public function __construct()
  {
	  parent::__construct();

	  $this->setClassName("rating_stars");

	  $this->setAttribute("value", 0);

  }
  public function setValue($value)
  {
	  $this->setAttribute("value", (float)$value);
	
  }

  public function getValue()
  {
	  return $value;
  }
  public function startRender()
  {

	  $attrs = $this->prepareAttributes();

	  if (!RatingStarsComponent::$script_initialized) {
		  echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/rating_stars.css' type='text/css'>";

		  echo "<script type='text/javascript' src='".SITE_ROOT."lib/js/rating_stars.js'></script>";
		  RatingStarsComponent::$script_initialized = true;
	  }
	  echo "<div $attrs>";
  }
  public function finishRender()
  {
	  echo "</div>";
  }
  public function renderImpl()
  {
	  $val= $this->getAttribute("value");


// 	$val = ($val/10);
	$min = $this->min;
	$max = $this->max;

	

	  for ($a=$min;$a<=$max;$a++){
		$sel = "off";
		if ($val>=$a){
			$sel = "on";
		}
		else if ($val<$a && $val > ($a-1)){
			$sel = "half";
		}
// 		else {
// 			$sel = "off";
// 		}
		echo "<div class='star $sel' value='$a'></div>";
	  }

	
  }



}
?>