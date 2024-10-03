<?php
include_once("components/renderers/items/DataIteratorItem.php");

class CallbackItemRenderer extends DataIteratorItem
{

    protected $callback;

    public function __construct($function_name)
    {
        parent::__construct();
        $this->setClassName("CallbackItemRenderer");

        $this->attributes["align"] = "left";
        $this->attributes["valign"] = "middle";

        if (!is_callable($function_name)) throw new Exception("$function_name not callable");
        $this->callback = $function_name;
    }

    protected function renderImpl()
    {

        call_user_func_array($this->callback, array(&$this->data, $this->getParent()));
    }

}

?>