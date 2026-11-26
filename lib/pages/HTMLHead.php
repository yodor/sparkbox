<?php
include_once("utils/url/URL.php");
include_once("utils/url/FileVersion.php");
include_once("components/Container.php");
include_once("components/Meta.php");
include_once("components/Link.php");
include_once("components/Script.php");
include_once("objects/IObserver.php");
include_once("components/ClosureComponent.php");
include_once("pages/SparkPage.php");
include_once("utils/AssetBundle.php");

class HTMLHead extends Container implements IObserver
{

    /**
     * The cascade layers, the later wins whatever the specificity:
     *  - base: the element rules of every stylesheet (selectors of elements only - body, h1, img, nav ul ...):
     *    they never win over a class rule, so they stay below the components
     *  - components: the building blocks of the core - one stylesheet per component class (Action, ColorButton,
     *    FormRenderer, InputField, TableView, TreeView, MenuBar, the dialogs ...), their plain look
     *  - core: the core frames and module contents (SparkPage, SparkAdminPage, faq, news)
     *  - store: the store frame, the store components and modules (sparkstore storefront/css)
     *  - site: the css of the site
     * Each stylesheet wraps its rules in its layer - '@layer components { ... }', its element rules in
     * '@layer base { ... }'. A building block is a default:
     * a frame, a module or a site adjusts it without a more specific selector.
     */
    protected static string $cssLayers = "@layer base, components, core, store, site;";

    protected static ?HTMLHead $instance = null;

    public static function Instance(): HTMLHead
    {
        if (self::$instance == null) {
            self::$instance = new HTMLHead();
        }
        return self::$instance;
    }

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
     * Array of Script
     * @var array<Script>
     */
    protected array $scripts = array();

    /**
     * Array holding the url parameter names that will be present in the canonical url version of 'this' page
     * @var array
     */
    protected array $canonical_params = array();

    /**
     * The local stylesheets and scripts in bundles (AssetBundle) - the public frames enable it (Config::ASSET_BUNDLES)
     */
    protected bool $bundles = false;

    /**
     * The scripts that run before the page is parsed (the src of addJS(..., true)) - in bundles: one bundle in the
     * place of the first of them; the other local scripts are one deferred bundle at the end of the head
     * @var array<string, bool>
     */
    protected array $bootScripts = array();

    /**
     * The addresses of the files the bundles have - for the page (document.Spark.bundled: ContentFragment does not
     * load them again)
     * @var array<string>
     */
    protected array $bundled = array();

    /**
     * The texts the scripts of the page show (addTexts) - English text => translation, for the JS tr()
     * @var array<string, string>
     */
    protected array $texts = array();


    private function __construct()
    {
        parent::__construct(false);
        //no css class
        $this->setClassName("");
        $this->setComponentClass("");

        $this->setTagName("head");

        $title = new Component();
        $title->setComponentClass("");
        $title->setTagName("title");

        $this->title = $title;
        $this->items()->append($this->title);

        $charset = new Meta();
        $charset->setAttribute("charset", "utf-8");
        $this->items()->append($charset);

        //the favicon of the site (favicon.ico in its root), else the SparkBox icon (sparkfront/images/sparkbox)
        $this->favicon = new Link();
        $this->favicon->setRelation("icon");
        if (is_file(Spark::Get(Config::APP_PATH) . "/favicon.ico")) {
            $this->favicon->setHref(Spark::Get(Config::LOCAL) . "/favicon.ico");
        }
        else {
            $this->favicon->setHref(Spark::Get(Config::SPARK_LOCAL) . "/images/sparkbox/favicon.ico");
            $touchIcon = new Link();
            $touchIcon->setRelation("apple-touch-icon");
            $touchIcon->setHref(Spark::Get(Config::SPARK_LOCAL) . "/images/sparkbox/apple-touch-icon.png");
            $this->items()->append($touchIcon);
        }
        $this->items()->append($this->favicon);

        $cssLayers = new Component(false);
        $cssLayers->setTagName("style");
        $cssLayers->setComponentClass("");
        $cssLayers->setContents(HTMLHead::$cssLayers);
        $this->items()->append($cssLayers);

        $meta = new ClosureComponent($this->renderMeta(...), false);
        $this->items()->append($meta);

        $css = new ClosureComponent($this->renderCSS(...), false);
        $this->items()->append($css);

        //the theme tokens - the palette and the font every component reads (sparkfront/css/themes/<THEME>.css); their
        //layer decides, not the order: the store and the site set them in later layers
        $theme = preg_replace("/[^a-z0-9_-]/", "", strtolower((string)Spark::Get(Config::THEME))) ?: "light";
        $this->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/themes/" . $theme . ".css");

        $script = new ClosureComponent($this->renderScripts(...), false);
        $this->items()->append($script);

        $canonical = new ClosureComponent($this->renderCanonical(...), false);
        $this->items()->append($canonical);




    }

    public function setTitle(string $text): void
    {
        $this->title->setText($text);
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

    /**
     * Bundle the local stylesheets into one and the local scripts into two (the boot ones, the deferred rest) -
     * AssetBundle. For the public pages: the inline code of the page must not need a deferred script before the
     * page is loaded (onPageLoad() - CallStack.js is a boot script).
     */
    public function enableBundles(bool $enabled = true) : void
    {
        $this->bundles = $enabled;
    }

    public function isBundled() : bool
    {
        return $this->bundles;
    }

    /**
     * The URL path of a local file address ('/sparkfront/css/Action.css?v=x' - '/sparkfront/css/Action.css'), ""
     * for an address of another host or a missing file
     */
    protected static function LocalPath(string $href) : string
    {
        if (!str_starts_with($href, "/") || str_starts_with($href, "//")) return "";
        $path = (string)parse_url($href, PHP_URL_PATH);
        $root = (string)($_SERVER["DOCUMENT_ROOT"] ?? "");
        return ($root !== "" && is_file($root . $path)) ? $path : "";
    }

    protected function renderCSS(): void
    {
        echo "<!-- CSS Files Start -->\n";

        $files = array();
        foreach ($this->cssLinks as $href => $cssLink) {
            if (!($cssLink instanceof Link)) continue;
            $path = $this->bundles ? HTMLHead::LocalPath($cssLink->getHref()) : "";
            if ($path === "") {
                $cssLink->render();
                continue;
            }
            $media = $cssLink->getAttribute("media");
            $files[] = $path . (($media !== "") ? "@" . $media : "");
            $this->bundled[] = $cssLink->getHref();
        }
        if (count($files) > 0) {
            $bundle = new Link();
            $bundle->setHref(AssetBundle::Href(AssetBundle::TYPE_CSS, $files));
            $bundle->render();
        }

        echo "<!-- CSS Files End -->\n";

    }

    protected function renderScripts(): void
    {
        if (!$this->bundles) {
            foreach ($this->scripts as $object) {
                if ($object instanceof Script) $object->render();
            }
            $this->renderTexts();
            return;
        }

        $boot = array();
        $deferred = array();
        foreach ($this->scripts as $object) {
            if (!($object instanceof Script) || $object->getSrc() === "") continue;
            $path = HTMLHead::LocalPath($object->getSrc());
            if ($path === "") continue;
            if (isset($this->bootScripts[$object->getSrc()])) $boot[] = $path;
            else $deferred[] = $path;
            $this->bundled[] = $object->getSrc();
        }

        $bootRendered = false;
        foreach ($this->scripts as $object) {
            if (!($object instanceof Script)) continue;
            $local = ($object->getSrc() !== "") && (HTMLHead::LocalPath($object->getSrc()) !== "");
            if (!$local) {
                $object->render();
                continue;
            }
            //the boot bundle in the place of the first boot script
            if (!$bootRendered && isset($this->bootScripts[$object->getSrc()])) {
                $script = new Script();
                $script->setSrc(AssetBundle::Href(AssetBundle::TYPE_JS, $boot));
                $script->render();
                $bootRendered = true;
            }
        }

        if (count($deferred) > 0) {
            $script = new Script();
            $script->setSrc(AssetBundle::Href(AssetBundle::TYPE_JS, $deferred));
            $script->setAttribute("defer");
            $script->render();
        }

        //the files of the bundles - a fragment does not load them again (ContentFragment)
        $list = new Script();
        $list->setCode("(document.Spark = document.Spark || {}).bundled = " . json_encode($this->bundled, JSON_UNESCAPED_SLASHES) . ";");
        $list->render();

        $this->renderTexts();
    }

    /**
     * The translations of the texts of the scripts (addTexts): document.Spark.texts - the JS tr() reads them
     */
    protected function renderTexts() : void
    {
        if (count($this->texts) === 0) return;
        $script = new Script();
        $script->setCode("(document.Spark = document.Spark || {}).texts = " . json_encode($this->texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . ";");
        $script->render();
    }

    /**
     * The texts (English, the keys of the language packs) the scripts of the page show with tr("...") - their
     * translations are given to the page (document.Spark.texts). A component adds the texts of its script when it is
     * created, before the head renders.
     * @param string ...$texts
     */
    public function addTexts(string ...$texts) : void
    {
        foreach ($texts as $text) {
            if ($text === "" || isset($this->texts[$text])) continue;
            $this->texts[$text] = tr($text);
        }
    }

    /**
     * @return array<string, string> the texts of the scripts of the page and their translations (addTexts)
     */
    public function texts() : array
    {
        return $this->texts;
    }

    protected function renderCanonical(): void
    {
        if (count($this->canonical_params) > 0) {
            $builder = SparkPage::Instance()->currentURL();
            $parameters = $builder->getParameterNames();
            foreach ($parameters as $name) {
                if (array_key_exists($name, $this->canonical_params)) {
                    if ($builder->get($name)->value()) continue;
                    //remove also empty valued parameters present in the canonical_params
                }
                $builder->remove($name);
            }
            $canonical_href = $builder->fullURL();
            echo "<link rel='canonical' href='$canonical_href'>\n";
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

    public function getMeta(string $name) : string
    {
        $result = "";
        if (isset($this->meta[$name])) {
            $meta = $this->meta[$name];
            if ($meta instanceof Meta) {
                $result = $meta->getContent();
            }
        }
        return $result;
    }

    public function haveMeta(string $name) : bool
    {
        return isset($this->meta[$name]);
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
     * A meta property tag by its full name, next to the Open Graph tags (e.g. 'product:price:amount')
     * @param string $property the full property name
     * @param string $value the contents of this tag
     */
    public function addPropertyTag(string $property, string $value) : void
    {
        $meta = new Meta();
        $meta->setProperty($property);
        $meta->setContent($value);
        $this->opengraph["property:$property"] = $meta;
    }

    /**
     * Adds a JavaScript code reference by URL to the javascript collection
     * @param string $url The url of the javascript code
     * @param bool $boot needed by the inline code of the page as it is parsed - not deferred in bundles
     */
    public function addJS(string $url, bool $boot = false) : void
    {
        $js = new Script();
        $js->setSrc(HTMLHead::Versioned($url));
        $this->addScript($js);
        //runs before the page is parsed also in bundles (enableBundles)
        if ($boot) $this->bootScripts[$js->getSrc()] = true;
    }

    /**
     * Local file URL with its file version added ('/sparkfront/css/Action.css?v=toie5w') - other URLs are
     * returned exactly as given
     * @param string $url
     * @return string
     */
    protected static function Versioned(string $url) : string
    {
        $versioned = new URL($url);
        if ($versioned->isAbsolute()) return $url;
        return $versioned->setVersion(new FileVersion($versioned->getScriptName())) ? $versioned->toString() : $url;
    }

    /**
     * Add a CSS link to this head CSS links collection
     * @param string $url The url of the CSS script.
     * @param bool $prepend first in the head
     * @param string $media the media query of the link ('(max-width: 1024px)') - the stylesheet applies only there
     */
    public function addCSS(string $url, bool $prepend=false, string $media="") : void
    {
        $cssLink = new Link();
        $cssLink->setHref(HTMLHead::Versioned($url));
        if ($media !== "") $cssLink->setAttribute("media", $media);
        //overwrite first
        $this->cssLinks[$url] = $cssLink;

        if ($prepend) {
            $this->cssLinks = array($url => $cssLink) + $this->cssLinks;
        }
    }

    /**
     * The addresses of the stylesheets of this head, in their order (versioned) - for a fragment (ContentFragmentResponder)
     * @return array<string>
     */
    public function stylesheetURLs() : array
    {
        $result = array();
        foreach ($this->cssLinks as $link) {
            if ($link instanceof Link) $result[] = $link->getHref();
        }
        return $result;
    }

    /**
     * The addresses of the script files of this head, in their order - for a fragment (ContentFragmentResponder)
     * @return array<string>
     */
    public function scriptURLs() : array
    {
        $result = array();
        foreach ($this->scripts as $script) {
            if ($script instanceof Script && $script->getSrc() !== "") $result[] = $script->getSrc();
        }
        return $result;
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

    public function onEvent(SparkEvent $event): void
    {
        if (!($event instanceof ComponentEvent)) return;
        if (!$event->isEvent(ComponentEvent::COMPONENT_CREATED)) return;
        
        $cmp = $event->getSource();
        if (!($cmp instanceof IHeadContents)) return;

        $css_files = $cmp->requiredStyle();
        foreach ($css_files as $key => $url) {
            $this->addCSS($url, true);
        }

        $js_files = $cmp->requiredScript();
        foreach ($js_files as $key => $url) {
            //no prepend here
            $this->addJS($url);
        }

    }
}