<?php
include_once("pages/SparkPage.php");
include_once("pages/TemplatePage.php");

/**
 * Plain page of a public module (pages, news, faq) - the default of the core modules. A layer or a site sets
 * its own page class in the module init.php (the TemplatePage trait on its page look). update() appends the
 * content component.
 */
class SparkModulePage extends SparkPage implements IObserver
{
    use TemplatePage;

    public function __construct()
    {
        parent::__construct();
        $this->templateConstruct();
    }

    public function update(TemplateContent $content) : void
    {
        $config = $content->config();
        if ($config->title) $this->setTitle($config->title);
        $this->items()->append($content->component());
    }
}
