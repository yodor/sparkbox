<?php
include_once("components/renderers/cells/TableCell.php");

class ClosureCell extends TableCell
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
        $component = new ClosureComponent(function(){
            ($this->closure)($this->data, $this->column);
        }, false);

        $this->items()->append($component);
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->data = $data;
        $this->setContents("");
    }

}

?>
