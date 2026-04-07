<?php
include_once("objects/SparkEvent.php");


class TemplateMenuEvent extends SparkEvent
{
    const string CREATED = "CREATED";
}

class TemplateEvent extends SparkEvent
{

    /**
     * After ($config->contentClass) is instantiated and observer is registered
     */
    const string CONTENT_CREATED = "CONTENT_CREATED";

    /**
     * After ($config->contentClass)->setup($config) - content already has the config assigned
     */
    const string CONTENT_SETUP = "CONTENT_SETUP";

    /**
     * After ($config->contentClass)->initialize() - main component of content is assigned
     */
    const string CONTENT_INITIALIZED = "CONTENT_INITIALIZED";

    /**
     * ($config->contentClass) is inserted into the page component
     */
    const string CONTENT_INSERTED = "CONTENT_INSERTED";

    /*
     * Input processing is done on the ($config->contentClass)
     */
    const string CONTENT_INPUT_PROCESSED = "CONTENT_INPUT_PROCESSED";

}