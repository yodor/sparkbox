<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class ClosureCellRenderer extends TableCellRenderer
{
    /**
     * @var Closure
     */
    protected Closure $closure;

    /**
     * @var array
     */
    protected array $data = array();

    public function __construct(Closure $closure)
    {
        parent::__construct();
        $this->closure = $closure;
    }

    protected function renderImpl()
    {
        ($this->closure)($this->data, $this->column);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->data = $data;
    }

}
?>
