<?php
include_once("utils/IHeadScript.php");
include_once("utils/OutputBuffer.php");

abstract class OutputScript implements IHeadScript
{
    protected string $type = "text/javascript";

    protected OutputBuffer $buffer;

    public function __construct()
    {
        $this->buffer = new OutputBuffer();
    }

    public function setType(string $type) : void
    {
        $this->type = $type;
    }

    /**
     * Render the script to buffer and return contents
     * @return string
     */
    abstract public function script(): string;

}
?>
