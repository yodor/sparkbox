<?php
include_once("components/Container.php");
include_once("components/Meta.php");
include_once("components/Link.php");
include_once("components/Script.php");

class HTMLHead extends Container
{

    /**
     * @var Component
     */
    protected Component $title;

    /**
     * @var Link
     */
    protected Link $favicon;

    /**
     * Array of Link rel=stylesheet using href as array key
     * @var array
     */
    protected array $cssLinks = array();

    /**
     * property
     * array of Strings representing URLs of all JavaScript that are used in this page
     */
    protected array $javaScripts = array();


    /**
     * Array of Meta using name as key
     */
    protected array $meta = array();

    /**
     * OpenGraph property name=>value
     * @var array
     */
    protected array $opengraph = array();

    /**
     * Array of Script|IScript
     * @var array
     */
    protected array $scripts = array();

    /**
     * Array holding the url parameter names that will be present in the canonical url version of 'this' page
     * @var array
     */
    protected array $canonical_params = array();


    public function __construct()
    {
        parent::__construct(false);
        //no css class
        $this->setClassName("");
        $this->setComponentClass("");

        $this->setTagName("HEAD");

        $this->addMeta("charset", "UTF-8");
        $this->addMeta("Content-Type", "text/html;charset=utf-8");
        $this->addMeta("Content-Style-Type", "text/css");


        $title = new Component();
        $title->setComponentClass("");
        $title->setTagName("TITLE");
        $title->setContents("%title%");
        $this->title = $title;
        $this->items()->append($this->title);

        //default favicon
        $this->favicon = new Link();
        $this->favicon->setRelation("shortcut icon");
        $this->favicon->setHref("//" . SITE_DOMAIN . LOCAL . "/favicon.ico");
        $this->items()->append($this->favicon);

        $meta = new ClosureComponent($this->renderMeta(...), false);
        $this->items()->append($meta);

        $css = new ClosureComponent($this->renderCSS(...), false);
        $this->items()->append($css);

        $script = new ClosureComponent($this->renderScripts(...), false);
        $this->items()->append($script);

        $canonical = new ClosureComponent($this->renderCanonical(...), false);
        $this->items()->append($canonical);

        $language = new ClosureComponent($this->renderLanguage(...), false);
        $this->items()->append($language);

    }

    public function setTitle(string $text): void
    {
        $this->title->setContents($text);
    }

    public function getTitle(): string
    {
        return $this->title->getContents();
    }

    public function favicon(): Link
    {
        return $this->favicon;
    }

    protected function renderMeta(): void
    {
        foreach ($this->meta as $name => $meta) {
            if (!($meta instanceof Meta)) continue;
            $meta->render();
        }

        foreach ($this->opengraph as $property => $meta) {
            if (!($meta instanceof Meta)) continue;
            $meta->render();
        }
    }

    protected function renderCSS(): void
    {
        echo "<!-- CSS Files Start -->\n";

        foreach ($this->cssLinks as $href => $cssLink) {
            if (!($cssLink instanceof Link)) continue;
            $cssLink->render();
        }

        echo "<!-- CSS Files End -->\n";

    }

    protected function renderScripts(): void
    {
        foreach ($this->scripts as $object) {
            if ($object instanceof Script) {
                $object->render();
            } else if ($object instanceof IScript) {
                $object->script()->render();
            }
        }
    }

    protected function renderCanonical(): void
    {
        if (count($this->canonical_params) > 0) {
            $builder = SparkPage::Instance()->currentURL();
            $parameters = $builder->getParameterNames();
            foreach ($parameters as $name) {
                if (array_key_exists($name, $this->canonical_params)) continue;
                $builder->remove($name);
            }
            $canonical_href = fullURL($builder->toString());
            echo "<link rel='canonical' href='$canonical_href'>\n";
        }
    }

    protected function renderLanguage(): void
    {
        $url = SparkPage::Instance()->currentURL()->fullURL();
        $x_default = new Link();
        $x_default->setRelation("alternate");
        $x_default->setHref($url->toString());
        //X-default tags are recommended, but not mandatory
        $x_default->setAttribute("hreflang", "x-default");
        $x_default->render();

        if (TRANSLATOR_ENABLED) {

            $lb = new LanguagesBean();
            $qry = $lb->queryFull();
            $num = $qry->exec();
            if ($num>1) {
                while ($result = $qry->nextResult()) {
                    if (!$result->isSet("lang_code")) continue;
                    $lang_code = $result->get("lang_code");
                    if (strlen($lang_code) < 2) continue;
                    $locale_default = new Link();
                    $locale_default->setRelation("alternate");
                    $url->add(new URLParameter("change_language", "1"));
                    $url->add(new URLParameter("langID", $result->get($lb->key())));
                    $locale_default->setHref($url->toString());
                    $locale_default->setAttribute("hreflang", substr($lang_code, 0, 2));
                    $locale_default->render();
                }
            }
        }
    }
    /**
     *  Add meta tag to be rendered into this page.
     * @param $name string The name attribute to add to the Meta collection
     * @param $content string The content attribute
     */
    public function addMeta(string $name, string $content) : void
    {
        $meta = new Meta();
        $meta->setName($name);
        $meta->setContent($content);

        $this->meta[$name] = $meta;
    }

    /**
     * Well known tag names
     * og:title	The title of the web page.
     * og:description	The description of the web page.
     * og:url	The canonical url of the web page.
     * og:image	URL to an image attached to the shared post.
     * og:type	A string that indicates the type of the web page. You can find one that is suitable for your web page here.
     * @param string $property The name of the tag without the leading 'og:'
     * @param string $value The contents of this tag
     */
    public function addOGTag(string $property, string $value) : void
    {
        $meta = new Meta();
        $meta->setProperty("og:$property");
        $meta->setContent($value);
        $this->opengraph[$property] = $meta;
    }

    /**
     * Adds a JavaScript code reference by URL to the javascript collection
     * @param string $url The url of the javascript code
     */
    public function addJS(string $url) : void
    {
        $js = new Script();
        $js->setSrc($url);
        $this->addScript($js);
    }

    /**
     * Add a CSS link to this head CSS links collection
     * @param string $url The url of the CSS script.
     */
    public function addCSS(string $url, bool $prepend=false) : void
    {
        $cssLink = new Link();
        $cssLink->setHref($url);
        //overwrite first
        $this->cssLinks[$url] = $cssLink;

        if ($prepend) {
            $this->cssLinks = array($url => $cssLink) + $this->cssLinks;
        }
    }

    /**
     * Add a JavaScript Script to this head JavaScript scripts collection
     * @param Script $object
     * @return void
     */
    public function addScript(Script $object) : void
    {
        $key = "";
        if ($object->getName()) {
            $key = $object->getName();
        }
        else if ($object->getSrc()) {
            $key = $object->getSrc();
        }
        if ($key) {
            $this->scripts[$key] = $object;
        }
        else {
            $this->scripts[] = $object;
        }

    }

    /**
     * Find all scripts matching PHP Class name
     * @param string $class_name
     * @return array
     */
    public function findScript(string $class_name) : array
    {
        $result = array();
        foreach ($this->scripts as $script) {
            if ($script instanceof $class_name) $result[] = $script;
        }
        return $result;
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






}
?>
