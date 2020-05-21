<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class ClosureCellRenderer extends TableCellRenderer
{
    /**
     * @var Closure
     */
    protected $closure;

    /**
     * @var array
     */
    protected $data;

    public function __construct(Closure $closure)
    {
        parent::__construct();
        $this->closure = $closure;
    }

    protected function renderImpl()
    {
        $closure = $this->closure;
        $closure($this->data, $this->column);
    }

    public function setData(array &$row)
    {
        parent::setData($row);
        $this->data = $row;
    }

}
?>