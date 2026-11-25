<?php

include_once("templates/TemplateContent.php");

class Plain extends TemplateContent
{
    /**
     * The config of a plain content: title (the page title) and description (the caption of the content - HTML)
     * @param string $title
     * @param string $description
     * @return TemplateConfig
     */
    public static function Configure(string $title = "", string $description = "") : TemplateConfig
    {
        $config = parent::Configure();
        //English texts of the code (keys of the language packs) or texts of the site
        $config->title = tr($title);
        $config->description = tr($description);
        return $config;
    }


    public function __construct()
    {
        parent::__construct();

    }

    public function initialize(): void
    {
        $this->cmp = new Container();
        //the description in the content - every frame shows it (the error content of a failing path, a section page)
        if ($this->config->description) {
            $this->cmp->setCaption($this->config->description);
        }
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