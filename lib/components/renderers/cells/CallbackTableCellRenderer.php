<?php
include_once("lib/components/renderers/cells/TableCellRenderer.php");
include_once("lib/components/TableColumn.php");

class CallbackTableCellRenderer extends TableCellRenderer implements ICellRenderer
{

    protected $callback = false;


    public function __construct($function_name)
    {
        parent::__construct();

        if (!is_callable($function_name)) throw new Exception("$function_name not callable");
        $this->callback = $function_name;
    }

    public function renderCell(array &$row, TableColumn $tc)
    {

        $this->processAttributes($row, $tc);

        $this->startRender();
        $prkey = $tc->getView()->getIterator()->key();


        call_user_func_array($this->callback, array(&$row, &$tc));


        $this->finishRender();
    }
}

?>