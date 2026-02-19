<?php
include_once("objects/data/GTAGObject.php");
include_once("objects/data/GTMConvParam.php");

class GTAGConversion extends GTAGObject
{
    public function __construct(string $conversionID)
    {
        parent::__construct();
        $this->setCommand(GTAGObject::COMMAND_EVENT);
        $this->setType("conversion");
        $this->addParameter("send_to",$conversionID);
    }
}