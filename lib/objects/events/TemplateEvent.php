<?php
include_once("objects/SparkEvent.php");


class TemplateMenuEvent extends SparkEvent
{
    const string CREATED = "CREATED";
}

class TemplateEvent extends SparkEvent
{

    const string CONTENT_CREATED = "CONTENT_CREATED";
    const string CONTENT_SETUP = "CONTENT_SETUP";

    const string CONTENT_INITIALIZED = "CONTENT_INITIALIZED";

    const string CONTENT_INSERTED = "CONTENT_INSERTED";

    const string CONTENT_INPUT_PROCESSED = "CONTENT_INPUT_PROCESSED";

}