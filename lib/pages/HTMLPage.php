<?php
include_once("lib/components/renderers/IRenderer.php");

abstract class HTMLPage implements IRenderer
{

    /**
     * Abstract class for rendering html pages
     */

    private static $instance = NULL;

    public static function Instance()
    {
        return self::$instance;
    }

    protected $page_class = "";

    public function __construct()
    {
        self::$instance = $this;
    }

    protected function htmlStart()
    {
        echo "<!DOCTYPE html>";

        $dir = ' DIR="' . Session::Get("page_dir") . '" ';

        echo "<html $dir  >\n";
        echo "\n";

    }

    protected function headStart()
    {

        echo "<HEAD>";
        echo "\n";

        echo "<TITLE>" . SITE_TITLE . "</TITLE>";
        echo "\n";

        $this->dumpMetaTags();
        echo "\n";

        echo "<!-- HTMLPage CSS start -->";
        $this->dumpCSS();
        echo "<!-- HTMLPage CSS end -->";
        echo "\n";

        echo "<!-- HTMLPage Callable CSS start -->";
        if (is_callable("dumpCSS")) {
            dumpCSS();
        }
        echo "<!-- HTMLPage Callable CSS end -->";
        echo "\n";

        echo "<!-- HTMLPage JavaScript start -->";
        $this->dumpJS();
        echo "<!-- HTMLPage JavaScript end -->";
        echo "\n";

        echo "<!-- HTMLPage Callable JavaScript start -->";
        if (is_callable("dumpJS")) {
            dumpJS();
        }
        echo "<!-- HTMLPage Callable JavaScript end -->";
        echo "\n";

    }

    protected function dumpMetaTags()
    {
        echo "<meta http-equiv='content-type' content='text/html;charset=utf-8'>\n";
        echo "<meta http-equiv='Content-Style-Type' content='text/css'>\n";

        //echo "<meta http-equiv='X-UA-Compatible' content='IE=9' >\n";
        //echo "<meta http-equiv='X-UA-Compatible' content='IE=8' >\n";
        echo "\n";
    }

    protected function dumpCSS()
    {
        echo "\n";
    }

    protected function dumpJS()
    {
        echo "\n";
    }

    protected function headEnd()
    {
        echo "</HEAD>\n";
        echo "\n";
    }

    protected function bodyStart()
    {
        echo "<BODY class='{$this->getPageClass()}'>";
        echo "\n";
    }


    protected function bodyEnd()
    {
        echo "\n";
        echo "</BODY>\n";
        echo "\n";
    }

    protected function htmlEnd()
    {
        echo "\n";
        echo "</HTML>";
        echo "\n";
    }


    public abstract function startRender();

    public abstract function finishRender();


    /**
     * Set the preferred CSS class name of this page
     * @param string $cls
     */
    public function setPageClass(string $cls)
    {
        $this->page_class = $cls;
    }

    /**
     * Return the manually set CSS class name for this page. If page class is not set returns automatic class name
     * corresponding to the php filename this page is running from
     * @return string Automatic class naming - The php class name + the folder this php script is in + the php script file name
     */
    public function getPageClass()
    {
        if ($this->page_class) return $this->page_class;

        $sname = str_replace(".php", "", basename($_SERVER["SCRIPT_NAME"]));
        $pname = basename(dirname($_SERVER["SCRIPT_NAME"]));
        return get_class($this) . " " . $pname . " " . $sname;
    }

    /**
     * Return the full URL this page is running from
     * @return string
     */
    public function getPageURL()
    {
        return $_SERVER["SCRIPT_NAME"] . "?" . $_SERVER["QUERY_STRING"];
    }

}

?>
