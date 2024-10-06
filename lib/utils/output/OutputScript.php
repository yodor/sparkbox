<?php
include_once("utils/IHeadScript.php");
include_once("utils/output/OutputBuffer.php");

abstract class OutputScript implements IHeadScript
{

    protected OutputBuffer $buffer;

    public function __construct()
    {
        $this->buffer = new OutputBuffer();
    }

    /**
     * OutputBuffer is already started - Output the script contents directly
     * @return void
     */
    abstract protected function fillBuffer() : void;

    /**
     * Render the script to buffer and return contents
     * @return string
     */
    public function script(): string
    {
        $this->buffer->start();
        $this->fillBuffer();
        $this->buffer->end();
        return $this->buffer->get();
    }

}
?>
