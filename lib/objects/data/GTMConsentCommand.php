<?php
include_once("objects/data/GTMCommand.php");
include_once("objects/data/GTMConvParam.php");

class GTMConsentCommand extends GTMCommand
{
    protected array $defaultConsent = array();

    public function __construct()
    {
        parent::__construct(GTMCommand::COMMAND_CONSENT);
        $this->setType("default");
        $this->addParameter("ad_user_data", "denied");
        $this->addParameter("ad_personalization", "denied");
        $this->addParameter("ad_storage", "granted");
        $this->addParameter("analytics_storage", "granted");
        $this->addParameter("wait_for_update", 5000);
    }
}