<?php

class ValueInterleave
{

    private $iter;

    public function __construct($cls_even, $cls_odd = false)
    {
        if (is_array($cls_even)) {
            $this->iter = new ArrayIterator($cls_even);
        }
        else {
            $this->iter = new ArrayIterator(array($cls_even, $cls_odd));
        }

    }

    public function advance()
    {
        $this->iter->next();
        if (!$this->iter->valid()) $this->iter->rewind();
    }

    public function value()
    {
        if (!$this->iter->valid()) $this->iter->rewind();

        return $this->iter->current();
    }

}

?>
