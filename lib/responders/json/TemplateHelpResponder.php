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

    /**
     * Help of the path from the module 'help/<language>/' folders - site, store, core (first found)
     * templates/modules/<module>/help/bgn/store.products.html
     */
    public function _fetch(JSONResponse $response) : void
    {
        $path = Template::FormatPath($_GET["path"], ".", false);

        $language = "bgn";
        $prefix = Module::Active()->getPreifx() . "/help/" . $language;
        $file = SparkLoader::Factory($prefix)->find($path, "html");

        if ($file !== "") {
            $response->message = file_get_contents($file);
        }
        else {
            $response->message = "<div class='error'>" . tr("Help is not available for this page") . "</div>";
        }
    }

}