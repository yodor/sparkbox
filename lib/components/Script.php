<?php
include_once("components/Container.php");
include_once("utils/url/URL.php");

class Script extends Container
{
    protected URL $srcURL;
    protected string $code = "";

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

    public function setCode(string $code) : void
    {
        $this->code = $code;
    }

    public function getCode() : string
    {
        return $this->code;
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

    protected function finalize(): void
    {
        parent::finalize();

        //priority 1 - external script referenced by src URL
        if ($this->srcURL->toString()) {
            $this->setAttribute("src", $this->srcURL->toString());
            //no contents if src is set
            $this->setContents("");
        }
        //priority 2 - inline code
        else if (!empty($this->code)) {
            $this->setContents("\n$this->code\n");
        }
    }

}