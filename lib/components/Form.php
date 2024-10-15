<?php
include_once("components/Container.php");

class Form extends Container
{

    const string METHOD_POST = "post";
    const string METHOD_GET = "get";
    protected string $method = Form::METHOD_POST;

    //default
    const string ENCTYPE_URLENCODED = "application/x-www-form-urlencoded";

    const string ENCTYPE_MULTIPART = "multipart/form-data";
    const string ENCTYPE_TEXT = "text/plain";

    protected string $enctype = "";

    public function __construct()
    {
        parent::__construct(false);
        $this->setTagName("FORM");
    }

    public function setMethod(string $method) : void
    {
        $this->method = $method;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setEnctype(string $enctype) : void
    {
        $this->enctype = $enctype;
    }

    protected function processAttributes(): void
    {

        parent::processAttributes();
        if ($this->method) {
            $this->setAttribute("method", $this->method);
        }
        if ($this->enctype) {
            $this->setAttribute("enctype", $this->enctype);
        }

    }


}