<?php
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/TableColumn.php");

class CallbackCellRenderer extends TableCellRenderer
{

    protected $callback = FALSE;

    protected array $data = array();

    public function __construct($function_name)
    {
        parent::__construct();

        if (!is_callable($function_name)) throw new Exception("$function_name not callable");
        $this->callback = $function_name;
    }

    protected function renderImpl()
    {
        call_user_func_array($this->callback, array($this->data, $this->column));
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->data = $data;
    }

}

?>
