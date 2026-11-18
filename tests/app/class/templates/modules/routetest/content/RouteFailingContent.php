<?php
include_once("templates/TemplateContent.php");

/**
 * Test content: fails in initialize() (an unknown row ...)
 */
class RouteFailingContent extends TemplateContent
{
    public function initialize() : void
    {
        throw new Exception("Route row not found");
    }
}
