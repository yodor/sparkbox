<?php
include_once("templates/TemplateContent.php");
include_once("components/Container.php");
include_once("components/Action.php");
include_once("pages/SparkPage.php");

/**
 * Test content: a container with an action (its stylesheet Action.css); sets the page title only when there is a page
 */
class FragmentTestContent extends TemplateContent
{
    public function initialize() : void
    {
        $this->cmp = new Container(false);
        $this->cmp->setComponentClass("fragment_test");
        $action = new Action("Fragment Action");
        $action->setURL(new URL("/fragmenttest/"));
        $this->cmp->items()->append($action);
        SparkPage::Instance()?->setMetaDescription("not here");
    }

    protected function getContentTitle() : string
    {
        return "Fragment Test";
    }
}
