<?php
include_once("components/Container.php");
include_once("utils/URLBuilder.php");

class HTMLPage extends Container
{

    /**
     * Do not wrap with DIV
     * @var bool
     */
    protected $wrapper_enabled = false;

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


    protected $async_defer = array();
    protected $preload = array();

    /**
     * property array of key=>value strings used to render all meta tags of this page
     */
    protected $meta = array();

    protected $page_class = "";

    public function __construct()
    {
        parent::__construct();

        $this->addMeta("charset","UTF-8");
        $this->addMeta("Content-Type", "text/html;charset=utf-8");
        $this->addMeta("Content-Style-Type", "text/css");
    }

    protected function htmlStart()
    {
        echo "<!DOCTYPE html>\n";

        $dir_attr = " DIR='" . Session::Get("page_dir") . "'";

        $lang = substr(DEFAULT_LANGUAGE_ISO3, 0,2);

        $lang_attr = " LANG='".$lang."'";

        echo "<HTML $dir_attr $lang_attr>\n";
    }

    protected function headStart()
    {
        echo "<HEAD>\n";


        echo "<TITLE>%title%</TITLE>\n";


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
            echo "<META name='" . htmlentities($name) . "' content='" . htmlentities($content) . "'>\n";
        }
    }

    protected function renderCSS()
    {
        echo "<!-- CSS Files Start -->\n";

        foreach ($this->css_files as $href => $usedBy) {
            $rel = "rel='stylesheet'";
            if (isset($this->preload[$href])) {
                $preload = $this->preload[$href];
                if ($preload["preload"]) {
                    $rel = "rel='preload' as='style' onload='this.rel=\"stylesheet\"'";
                }
            }
            echo "<link $rel href='$href'>\n";
            echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->\n";
        }

        echo "<!-- CSS Files End -->\n";

    }

    protected function renderJS()
    {
        echo "<!-- JavaScript Files Start -->\n";

        foreach ($this->js_files as $src => $usedBy) {
            $async = "";
            $defer = "";
            if (isset($this->async_defer[$src])) {
                $async_defer = $this->async_defer[$src];
                $async = ($async_defer["async"]) ? "async" : "";
                $defer = ($async_defer["defer"]) ? "defer" : "";
            }
            echo "<script $async $defer type='text/javascript' src='$src'></script>\n";
            echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->\n";
        }
        echo "<!-- JavaScript Files End -->\n";

    }

    protected function headEnd()
    {
        echo "</HEAD>\n";
    }

    protected function bodyStart()
    {
        echo "<BODY class='{$this->getPageClass()}'>\n";
    }

    protected function bodyEnd()
    {
        echo "</BODY>\n";
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
     * Set the preferred CSS class name of this page and override the automatic class name
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
    public function getPageClass() : string
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
    public function getPageURL() : string
    {
        return currentURL();
    }

    public function getURL(): URLBuilder
    {
        $url = new URLBuilder();
        $url->buildFrom($this->getPageURL());
        return $url;
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
    public function getMeta(string $name) : string
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : "";
    }

    /**
     * Adds a CSS file to this page CSS scripts collection
     * @param string $filename The filename of the CSS script.
     */
    public function addCSS(string $filename, string $className = "", bool $prepend = FALSE, bool $preload = FALSE)
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

        $this->preload[$filename] = array("preload"=>$preload);

    }

    /**
     * Adds a JavaScript file to page JavaScripts collection
     * @param string $filename The filename of the javascript.
     */
    public function addJS(string $filename, string $className = "", bool $prepend = FALSE, bool $async = FALSE, bool $defer = FALSE)
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

        $this->async_defer[$filename] = array("async"=>$async, "defer"=>$defer);
    }

}

?>
