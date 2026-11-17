<?php
include_once("templates/TemplateContent.php");
include_once("components/Container.php");
include_once("components/TextComponent.php");
include_once("components/Action.php");

/**
 * The page of an unknown module path (404, Template::NotFoundConfig): its title, a short text and a link to the
 * site home - in the look of the module page
 */
class NotFound extends TemplateContent
{
    public function initialize(): void
    {
        $this->cmp = new Container(false);
        $this->cmp->setComponentClass("NotFound");

        $caption = new TextComponent("", "Caption");
        $caption->setTagName("h1");
        $caption->setText($this->config->title);
        $this->cmp->items()->append($caption);

        $text = new TextComponent("", "Text");
        $text->setTagName("p");
        $text->setText($this->config->description);
        $this->cmp->items()->append($text);

        $home = new Action(tr("Home page"), Spark::Get(Config::LOCAL) . "/");
        $home->setContents(tr("Home page"));
        $home->setComponentClass("Home");
        $this->cmp->items()->append($home);
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return $this->config->title;
    }
}
