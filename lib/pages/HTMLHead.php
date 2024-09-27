<?php
include_once("components/Component.php");

class HTMLHead extends Component
{

    protected string $title = "%title%";

    protected array $opengraph_tags = array();

    /**
     * property
     * array of Strings representing URL of all css files used
     */
    protected array $css_files = array();

    /**
     * property
     * array of Strings representing URLs of all JavaScript that are used in this page
     */
    protected array $js_files = array();
    protected array $async_defer = array();
    protected array $preload = array();

    protected string $favicon = "";

    /**
     * property array of key=>value strings used to render all meta tags of this page
     */
    protected array $meta = array();

    /**
     * Array holding the url parameter names that will be present in the canonical url version of 'this' page
     * @var array
     */
    protected array $canonical_params = array();


    protected array $head_scripts = array();


    public function __construct()
    {
        parent::__construct(false);
        //no css class
        $this->setClassName("");
        $this->setComponentClass("");

        $this->tagName = "head";

        $this->addMeta("charset","UTF-8");
        $this->addMeta("Content-Type", "text/html;charset=utf-8");
        $this->addMeta("Content-Style-Type", "text/css");

        //default favicon
        $this->favicon = "//" . SITE_DOMAIN . LOCAL."/favicon.ico";

    }

    public function setTitle(string $text) : void
    {
        $this->title = $text;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function addScript(IHeadScript $ihead_script) : void
    {
        $this->head_scripts[] = $ihead_script;
    }

    public function findScript(string $class_name) : array
    {
        $result = array();
        foreach ($this->head_scripts as $script) {
            if ($script instanceof $class_name) $result[] = $script;
        }
        return $result;
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
        return $this->meta[$name] ?? "";
    }

    public function addCanonicalParameter(...$names) : void
    {
        foreach ($names as $name) {
            $this->canonical_params[$name] = 1;
        }
    }

    public function canonicalParameters() : array
    {
        return $this->canonical_params;
    }

    protected function renderImpl()
    {
        echo "<TITLE>$this->title</TITLE>\n";

        $this->renderMeta();
        echo "\n";

        $this->renderOGMeta();
        echo "\n";

        $this->renderJS();
        echo "\n";

        $this->renderCSS();
        echo "\n";

        echo "<link rel='shortcut icon' href='$this->favicon'>";
        echo "\n";

        foreach ($this->head_scripts as $object) {
            if ($object instanceof IHeadScript) {
                echo $object->script();
            }
        }

        $url = URL::Current()->fullURL()->toString();
        //X-default tags are recommended, but not mandatory
        echo "<link rel='alternate' hreflang='x-default' href='$url'>\n";

        $default_locale = "".DEFAULT_LOCALE;
        echo "<link rel='alternate' hreflang='{$default_locale}' href='$url'>\n";


        if (count($this->canonical_params)>0) {
            $builder = URL::Current();
            $parameters = $builder->getParameterNames();
            foreach ($parameters as $name) {
                if (array_key_exists($name, $this->canonical_params)) continue;
                $builder->remove($name);
            }
            $canonical_href = fullURL($builder->toString());
            echo "<link rel='canonical' href='$canonical_href'>\n";
        }


        ?>
        <!-- SparkPage local script start -->
        <script type='text/javascript'>
            let LOCAL = "<?php echo LOCAL;?>";
            let SPARK_LOCAL = "<?php echo SPARK_LOCAL;?>";
            let STORAGE_LOCAL = "<?php echo STORAGE_LOCAL;?>";
        </script>
        <!-- SparkPage local script end -->
        <?php
    }


    protected function renderMeta() : void
    {
        foreach ($this->meta as $name => $content) {
            echo "<META name='" . attributeValue($name) . "' content='" . attributeValue($content) . "'>\n";
        }
    }

    /**
     * Adds a CSS file to this page CSS scripts collection
     * @param string $filename The filename of the CSS script.
     */
    public function addCSS(string $filename, string $className = "", bool $prepend = FALSE, bool $preload = FALSE)
    {
        //debug("Adding CSS: ".$filename." - Prepend: $prepend");
        //debug
//        if (!$className) $className = get_class($this);
//        $usedBy = array();
//        if (isset($this->css_files[$filename])) {
//            $usedBy = $this->css_files[$filename];
//        }
//        $usedBy[$className] = 1;
        $usedBy = 1;
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
        //debug
//        if (!$className) $className = get_class($this);
//        $usedBy = array();
//        if (isset($this->js_files[$filename])) {
//            $usedBy = $this->js_files[$filename];
//
//        }
//        $usedBy[$className] = 1;
        $usedBy = 1;
        $this->js_files[$filename] = $usedBy;

        if ($prepend) {
            unset($this->js_files[$filename]);
            $this->js_files = array($filename => $usedBy) + $this->js_files;
        }

        $this->async_defer[$filename] = array("async"=>$async, "defer"=>$defer);
    }


    /**
     * Well known tag names
     * og:title	The title of the web page.
     * og:description	The description of the web page.
     * og:url	The canonical url of the web page.
     * og:image	URL to an image attached to the shared post.
     * og:type	A string that indicates the type of the web page. You can find one that is suitable for your web page here.
     * @param string $tag_name The name of the tag without the leading 'og:'
     * @param string $tag_content The contents of this tag
     */
    public function addOGTag(string $tag_name, string $tag_content)
    {
        $this->opengraph_tags[$tag_name] = $tag_content;
    }

    protected function renderOGMeta() : void
    {
        foreach ($this->opengraph_tags as $tag_name => $tag_content) {
            echo "<META property='og:$tag_name' content='" . attributeValue($tag_content) . "'>\n";
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
            //debug
            //echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->\n";
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
            //debug
            //echo "<!-- Used by: " . implode("; ", array_keys($usedBy)) . " -->\n";
        }
        echo "<!-- JavaScript Files End -->\n";

    }

}
?>
