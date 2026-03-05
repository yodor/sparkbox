<?php
include_once("responders/json/JSONResponder.php");

class TemplateHelpResponder extends JSONResponder
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function parseParams(): void
    {
        parent::parseParams();
        if (!isset($_GET["path"])) throw new Exception("No path specified");
    }

    public function _fetch(JSONResponse $response) : void
    {

        $path = Template::FormatPath($_GET["path"], ".", false);

        $language = "bgn";
        $parts = ["help", $language, "$path.html"];

        $file = implode("/", $parts);

        if (file_exists($file)) {
            $response->message = file_get_contents($file);
        }
        else {
            $response->message = "<div class='error'>Help file not found: $file</div>";
        }

    }

}