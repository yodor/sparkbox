<?php
include_once("pages/SparkPage.php");
include_once("templates/Template.php");

class SparkTemplatePage extends SparkPage implements IObserver
{
    //
    protected string $path = "home";

    public function __construct()
    {
        parent::__construct();


        SparkEventManager::register(TemplateEvent::class, $this);

        SparkEventManager::register(TemplateConfigEvent::class, $this);

        $this->addParameterName("path");

        if (isset($_GET["path"])) {
            $this->path = Template::FormatPath($_GET["path"],"/", true);
        }
    }

    /**
     * Load required configuration using the path url parameter
     * Set $this->path to the contents of $_GET["path"]
     * If $_GET["path"] is empty loads path = "home" but does not update $this->path
     * Calls Template::PathConfig
     * @return void
     */
    public function initialize() : void
    {
        try {
            Template::PathConfig($this->path);
            $this->body->setAttribute("path", $this->path);
        }
        catch (Exception $e) {
            Debug::ErrorLog("PathConfig failed: ".$e->getMessage());
            Template::ErrorConfig("Error:{$this->path}", $e->getMessage()." - ".$e->getTraceAsString());
        }
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

    /**
     * Handle calling Template::LoadContent when Template::Active() is changed
     * Calls the update() method after event TemplateEvent::CONTENT_INPUT_PROCESSED
     * @param SparkEvent $event
     * @return void
     * @throws Exception
     */
    public function onEvent(SparkEvent $event) : void
    {
        //config is created load its content
        if ($event->isEvent(TemplateConfigEvent::UPDATE)) {
            Template::LoadContent();
        }
        else if ($event->isEvent(TemplateEvent::CONTENT_INPUT_PROCESSED)) {
            $content = $event->getSource();
            if (!($content instanceof TemplateContent)) throw new Exception("Incorrect event source - expecting TemplateContent");
            $this->update($content);
        }
    }

    //
    public function currentURL(): URL
    {
        return Module::PathURL("", parent::currentURL());
    }
}