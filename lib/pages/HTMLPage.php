<?php
include_once("components/renderers/IRenderer.php");

abstract class HTMLPage implements IRenderer
{

    /**
     * property
     * array of Strings representing URL of all css files used
     */
    protected $css_files = array();

    /**
     * property
     * array of Strings representing URLs of all JavaScript that are used in this page
     */
    protected $js_files = array();

    /**
     * property array of key=>value strings used to render all meta tags of this page
     */
    protected $meta = array();

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

        $this->addMeta("Content-Type", "text/html;charset=utf-8");
        $this->addMeta("Content-Style-Type", "text/css");
    }

    protected function htmlStart()
    {
        echo "<!DOCTYPE html>";

        $dir = ' DIR="' . Session::Get("page_dir") . '" ';

        echo "<HTML $dir>";
    }

    protected function headStart()
    {
        echo "<HEAD>";
        echo "\n";

        echo "<TITLE>%title%</TITLE>";
        echo "\n";

        $this->renderMetaTags();
        echo "\n";

        $this->renderCSS();
        echo "\n";

        $this->renderJS();
        echo "\n";
    }

    protected function renderMetaTags()
    {
        foreach ($this->meta as $name => $content) {
            echo "<META name='" . htmlentities($name) . "' content='" . htmlentities($content) . "'>";
        }
    }

    protected function renderCSS()
    {
        echo "<!-- CSS Files Start -->";

        foreach ($this->css_files as $href => $usedBy) {
            echo "<link rel='stylesheet' href='$href' type='text/css' >";
            echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->";
        }

        echo "<!-- CSS Files End -->";

    }

    protected function renderJS()
    {
        echo "<!-- JavaScript Files Start -->";

        foreach ($this->js_files as $src => $usedBy) {
            echo "<script type='text/javascript' src='$src'></script>";
            echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->";
        }
        echo "<!-- JavaScript Files End -->";

    }

    protected function headEnd()
    {
        echo "</HEAD>";
    }

    protected function bodyStart()
    {
        echo "<BODY class='{$this->getPageClass()}'>";
    }

    protected function bodyEnd()
    {
        echo "</BODY>";
    }

    protected function htmlEnd()
    {
        echo "</HTML>";
    }

    public function startRender()
    {
        $this->htmlStart();

        $this->headStart();
        $this->headEnd();

        $this->bodyStart();
    }

    public function finishRender()
    {
        $this->bodyEnd();
        $this->htmlEnd();
    }


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

    /**
     *  Add meta tag to be rendered into this page.
     * @param $name string The name attribute to add to the Meta collection
     * @param $content string The content attribute
     */
    public function addMeta(string $name, string $content)
    {
        $this->meta[$name] = $content;
    }

    /**
     *  Get the content attribute of the meta
     * @param $name string The name attribute to
     * @return string The content attribute as set to the $name
     */
    public function getMeta(string $name)
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : "";
    }

    /**
     * Adds a CSS file to this page CSS scripts collection
     * @param string $filename The filename of the CSS script.
     */
    public function addCSS(string $filename, string $className = "", bool $prepend = FALSE)
    {
        if (!$className) $className = get_class($this);
        $usedBy = array();
        if (isset($this->css_files[$filename])) {
            $usedBy = $this->css_files[$filename];
        }
        $usedBy[$className] = 1;
        $this->css_files[$filename] = $usedBy;

        if ($prepend) {
            unset($this->css_files[$filename]);
            $this->css_files = array($filename => $usedBy) + $this->css_files;
        }
    }

    /**
     * Adds a JavaScript file to page JavaScripts collection
     * @param string $filename The filename of the CSS script.
     */
    public function addJS(string $filename, string $className = "", bool $prepend = FALSE)
    {
        if (!$className) $className = get_class($this);
        $usedBy = array();
        if (isset($this->js_files[$filename])) {
            $usedBy = $this->js_files[$filename];

        }
        $usedBy[$className] = 1;
        $this->js_files[$filename] = $usedBy;

        if ($prepend) {
            unset($this->js_files[$filename]);
            $this->js_files = array($filename => $usedBy) + $this->js_files;
        }
    }

}

?>
