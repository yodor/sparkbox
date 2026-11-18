<?php
include_once("templates/TemplateContent.php");
include_once("components/TextComponent.php");

/**
 * Test content: no title of its own - the frame uses fallbackTitle(); finalizeHead() sees the final head
 */
class RouteFallbackContent extends TemplateContent
{
    public function initialize() : void
    {
        $this->cmp = new TextComponent("fallback content");
        SparkPage::Instance()?->setMetaDescription("Route description");
    }

    public function fallbackTitle() : string
    {
        return "Route Fallback";
    }

    protected function getContentTitle() : string
    {
        return "";
    }

    public function finalizeHead(SparkPage $page) : void
    {
        $page->head()->addMeta("route-final", $page->head()->getTitle() . "|" . $page->getMetaDescription());
    }
}
