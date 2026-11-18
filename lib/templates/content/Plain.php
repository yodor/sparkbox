<?php

include_once("templates/TemplateContent.php");

class Plain extends TemplateContent
{
    /**
     * The config of a plain content: title and description (the frame shows them)
     * @param string $title
     * @param string $description
     * @return TemplateConfig
     */
    public static function Configure(string $title, string $description) : TemplateConfig
    {
        $config = TemplateConfig::Content(static::class);
        $config->title = $title;
        $config->description = $description;
        return $config;
    }


    public function __construct()
    {
        parent::__construct();

    }

    public function initialize(): void
    {
        $this->cmp = new Container();
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    public function getContentTitle(): string
    {
        return "PlainContent";
    }

}