<?php
include_once("objects/data/GTMCommand.php");
include_once("objects/data/GTMConvParam.php");

class GTMConversionCommand extends GTMCommand
{
    public function __construct(string $conversionID)
    {
        parent::__construct();
        $this->setCommand(GTMCommand::COMMAND_EVENT);
        $this->setType("conversion");
        $this->addParameter("send_to",$conversionID);
    }
}