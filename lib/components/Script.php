<?php
include_once("components/Container.php");
include_once("utils/url/URL.php");

class Script extends Container
{
    protected URL $srcURL;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("");
        $this->setTagName("script");
        $this->setType("text/javascript");
        $this->srcURL = new URL();
    }

    public function setType(string $type) : void
    {
        $this->setAttribute("type", $type);
    }

    public function getType() : string
    {
        return $this->getAttribute("type");
    }

    public function setSrc(string $src) : void
    {
        $this->srcURL->fromString($src);
    }
    public function getSrc() : string
    {
        return $this->srcURL->toString();
    }

    public function getURL() : URL
    {
        return $this->srcURL;
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();
        if ($this->srcURL->toString()) {
            $this->setAttribute("src", $this->srcURL);
        }

    }

}