<?php

class ValueInterleave
{


    protected ?ArrayObject $obj = NULL;
    protected ?ArrayIterator $iterator = NULL;

    public function __construct(array $items=array("even", "odd"))
    {
        $this->obj = new ArrayObject( $items );
        $this->iterator = $this->obj->getIterator();
    }


    public function advance() : void
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