<?php
include_once("components/Container.php");

/**
 * The help pane of the admin frame - filled by the help action (TemplateHelp.js, TemplateHelpResponder)
 */
class AdminHelpPane extends Container
{
    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("helpPane");
    }

    public function stylesheets() : array
    {
        return array(Spark::Get(Config::SPARK_LOCAL) . "/css/admin/AdminHelpPane.css");
    }
}
