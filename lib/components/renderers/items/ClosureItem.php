<?php
include_once("components/renderers/items/DataIteratorItem.php");

class ClosureItem extends DataIteratorItem
{

    protected ?Closure $closure = null;

    /**
     * Calls closure during setData with parameters $data and $this
     * @param Closure|null $closure
     */
    public function __construct(?Closure $closure=null)
    {
        parent::__construct();
        $this->setClassName("ClosureItem");

        $this->closure = $closure;
    }

    public function getClosure(): ?Closure
    {
        return $this->closure;
    }

    public function setClosure(Closure $closure) : void
    {
        $this->closure = $closure;
    }

    public function setData(array $data) : void
    {
        if ($this->closure instanceof Closure) {
            ($this->closure)($data, $this);
        }
    }

}

?>