<?php

class ValueInterleave
{


    protected $obj = NULL;
    protected $iterator = NULL;

    public function __construct(array $items=array("even", "odd"))
    {
        $this->obj = new ArrayObject( $items );
        $this->iterator = $this->obj->getIterator();
    }


    public function advance()
    {
        $this->iterator->next();
        if (!$this->iterator->valid()) {
            $this->iterator->rewind();
        }
    }

    public function value() : string
    {
        return $this->iterator->current();
    }

}

?>