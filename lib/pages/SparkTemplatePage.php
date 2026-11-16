<?php
include_once("pages/SparkPage.php");
include_once("pages/TemplatePage.php");

/**
 * SparkPage hosting a module (the TemplatePage trait) - the base of the admin page. update() does nothing here.
 */
class SparkTemplatePage extends SparkPage implements IObserver
{
    use TemplatePage;

    public function __construct()
    {
        parent::__construct();
        $this->templateConstruct();
    }

    /**
     * Handle adding the content to the page
     * Default implementation do nothing
     * @param TemplateContent $content
     * @return void
     */
    public function update(TemplateContent $content) : void
    {
        //Debug::ErrorLog("Processing content");
    }
}
