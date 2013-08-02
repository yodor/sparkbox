<?php

include_once ("lib/components/Component.php");
include_once ("lib/components/ListView.php");

include_once ("lib/components/renderers/IItemRenderer.php");

class CallbackItemRenderer extends Component implements IItemRenderer
{
  
  protected $callback;
  protected $item;

  public function setItem($item) {
	  $this->item = $item;
  }
  public function getItem() {
	  return $this->item;
  }

  public function __construct($function_name)
  {
	  parent::__construct();
	  $this->attributes["align"]="left";
	  $this->attributes["valign"]="middle";

	  if (!is_callable($function_name)) throw new Exception("$function_name not callable");
	  $this->callback = $function_name;
  }

  public function startRender()
  {
	  $all_attr = $this->prepareAttributes();
	  echo "<div $all_attr >";

  }
  public function finishRender()
  {

	  echo "</div>";
  }
  protected function renderImpl()
  {
	 
	 call_user_func_array($this->callback, array(&$this->item, $this->getParent()));
  }

//   public function renderItem($row, ListView $tc) {
// 	  $this->startRender();
// 	 
// 	  $this->finishRender();
//   }

  public function renderSeparator($idx_curr, $items_total) {

  }
}

?>