<?php
include_once("objects/SparkEvent.php");

class TemplateEvent extends SparkEvent
{
    const string CONFIG_CHANGED = "CONFIG_CREATED";

    const string CONTENT_CREATED = "CONTENT_CREATED";
    const string CONTENT_SETUP = "CONTENT_SETUP";

    const string CONTENT_INITIALIZED = "CONTENT_INITIALIZED";

    const string CONTENT_INSERTED = "CONTENT_INSERTED";

    const string CONTENT_INPUT_PROCESSED = "CONTENT_INPUT_PROCESSED";
    const string MENU_CREATED = "MENU_CREATED";

    public function __construct(string $name = "", ?SparkObject $source = null)
    {
        parent::__construct($name, $source);
    }
}