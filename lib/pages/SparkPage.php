<?php
include_once("storage/CacheFactory.php");
include_once("pages/HTMLPage.php");
include_once("responders/RequestController.php");
include_once("objects/SparkEventManager.php");

include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/HTMLTemplate.php");

include_once("beans/ConfigBean.php");

include_once("dialogs/MessageDialog.php");
include_once("components/ImagePopup.php");
include_once("utils/IActionCollection.php");
include_once("objects/ActionCollection.php");
include_once("utils/script/FBPixel.php");
include_once("utils/script/GTAG.php");
include_once("components/SessionAlertScript.php");


class SparkPage extends HTMLPage implements IActionCollection, IGETConsumer
{


    private static ?SparkPage $instance = NULL;

    public static function Instance() : ?SparkPage
    {
        return self::$instance;
    }

    /**
     * @var array
     */
    protected array $parameter_names = array();

    /**
     * The Preferred title of this page for rendering into the <TITLE></TITLE> tag
     */
    protected string $preferred_title = "";

    /**
     * Meta tag 'Description' overload. If not empty is used instead of ConfigBean 'seo' section value
     */
    protected string $description = "";



    /**
     * @var ActionCollection
     */
    protected ActionCollection $actions;



    protected bool $canonical_enabled = false;

    /**
     * Array holding the url parameter names that will be present in the canonical url version of 'this' page
     * @var array
     */
    protected array $canonical_params = array();

    /**
     * The content of the module path (the module engine - setContent())
     * @var TemplateContent|null
     */
    protected ?TemplateContent $pageContent = null;

    /**
     * The address of this page set by its content (a page with its own URL - /pages/<id>/<slug>/): canonical and
     * language links use it
     * @var URL|null
     */
    protected ?URL $contentURL = null;

    public function setMetaDescription(string $description) : void
    {
        $this->description = $description;
    }

    /**
     * The meta description of the page (final after applyTitleDescription() - from the render on)
     * @return string
     */
    public function getMetaDescription() : string
    {
        return $this->description;
    }

    public function getActions(): ActionCollection
    {
        return $this->actions;
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    protected function headInitialize() : void
    {
        $this->head()->addMeta("viewport", "width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=yes");

        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/SparkPage.css");

        $sparkJS = new Script();
        $local = Spark::Get(Config::LOCAL);
        $spark_local = Spark::Get(Config::SPARK_LOCAL);
        $storage_local = Spark::Get(Config::STORAGE_URL);

$configJS = <<<JS
document.Spark = {
    LOCAL: "$local",
    SPARK_LOCAL: "$spark_local",
    STORAGE_LOCAL: "$storage_local"
};
JS;
        $sparkJS->setCode($configJS);
        $this->head()->addScript($sparkJS);

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/SparkObject.js");
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/SparkEvent.js");

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/CallStack.js");
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/TemplateFactory.js");

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/Component.js");
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/TemplateComponent.js");

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/JSONRequest.js");
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/JSONComponent.js");
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/ContentFragment.js");

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/Tooltip.js");

        //cookies library
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/js.cookie.min.js");

        //gtm
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/SparkGTM.js");

        //use gtm
        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/SparkCookies.js");
    }
    /**
     * Set the default static instance
     * Sets default css / js
     * @throws Exception
     */
    public function __construct()
    {
        Debug::ErrorLog("--- CTOR ---");

        parent::__construct();

        self::$instance = $this;

        $this->headInitialize();

        $this->actions = new ActionCollection();

        //default template for showAlert
        $dialog = new MessageDialog();

    }


    public function initialize() : void
    {
        //
    }

    /**
     * The module engine puts the content of the path into this frame (after the content processed its input):
     * the title of its config and its component - appended to the page here; a frame puts it where its main part is
     * @param TemplateContent $content
     * @return void
     */
    public function setContent(TemplateContent $content) : void
    {
        $this->pageContent = $content;

        $config = $content->config();
        if ($config->title) $this->setTitle($config->title);

        $this->items()->append($content->component());
    }

    /**
     * The content of the module path - null for a page without one
     * @return TemplateContent|null
     */
    public function getContent() : ?TemplateContent
    {
        return $this->pageContent;
    }

    /**
     * The content's own address - canonical and language links
     * @param URL $url
     * @return void
     */
    public function setContentURL(URL $url) : void
    {
        $this->contentURL = $url;
    }

    /**
     * Set the contents of the TITLE tag
     * @param string $text
     */
    public function setTitle(string $text) : void
    {
        $this->preferred_title = $text;
    }

    /**
     * Get the contents of the TITLE tag
     * @return string
     */
    public function getTitle(): string
    {
        return $this->preferred_title;
    }

    /**
     * Assign values to preferred_title and description.
     * Default implementation: the fallback title of the content when no title is set, the meta_description of
     * the config seo section when no description is set
     *
     * @return void
     */
    protected function applyTitleDescription() : void
    {
        //no title from the content or the frame: the default title of the content
        if (!$this->preferred_title && $this->pageContent instanceof TemplateContent) {
            $this->preferred_title = $this->pageContent->fallbackTitle();
        }

        //the site default: the SEO settings of the admin (config section 'seo') - a site with a database
        if (!$this->description && Spark::GetBoolean(Config::DB_ENABLED)) {
            $config = ConfigBean::Factory();
            $config->setSection("seo");
            $this->description = (string)$config->get("meta_description");
        }
    }

    /**
     * Finalize head contents. Called before startRender() and after
     * applyTitleDescription()
     *
     * Defaul implementation transfer $preferred_title and $description
     * properties to the head() component
     *
     * @return void
     */
    protected function headFinalize() : void
    {
        $title = strip_tags($this->preferred_title);
        $this->head()->setTitle($title);

        $meta_description = Spark::MetaDescription($this->description);
        $this->head()->addMeta("description", $meta_description);
    }

    /**
     * Start rendering of this page
     * Two buffers are sent to client
     * 1. The head contents buffer - sent in startRender()
     * 2. The body contents buffer - everything between startRender() and finishRender() calls
     */
    public function startRender(): void
    {
        //head output buffer
        ob_start();

        //the request step (responders, session alert, session closed) - done by the module engine for the module
        //pages, here for the plain scripts
        RequestController::Respond();

        //assign values to preferred_title and description properties
        $this->applyTitleDescription();

        //do any final changes to the head contents
        //transfer preferred_title and description to the head section
        $this->headFinalize();

        //the content adds what depends on the final head of the frame (TemplateContent::finalizeHead)
        if ($this->pageContent instanceof TemplateContent) $this->pageContent->finalizeHead($this);

        parent::startRender();
        //<body> is in output buffer now

        //push head until including the body tag - browser can fetch css and scripts while we do the body contents
        ob_end_flush();



        //body output buffer
        ob_start(null, 4096);
    }

    /**
     * Finalize rendering of this page and send the body output buffer
     * 1. Render final components rendering ie all before the closing BODY tag
     * 3. Render the closing BODY tag
     * 4. Render the closing HTML tag
     * 5. End output buffering and send to client
     */
    public function finishRender(): void
    {
        //still inside the body section
        parent::finishRender();
        //</html> ended here

        ob_end_flush();

    }

    /**
     * The address of this page for its links (paging, sorting, filters, canonical) - default the address the
     * visitor requested (URL::Requested()): under a module rewrite it is the public address ('/authors'),
     * not the script that answers it ('/index.php?path=/authors'); the content's own address when it set one
     * (setContentURL).
     * @return URL
     */
    public function currentURL() : URL
    {
        if ($this->contentURL instanceof URL) return clone $this->contentURL;
        $url = URL::Requested();
        //a request of a module script itself (index.php?path=...) as its module path address
        if ($url->contains("path") && class_exists("Module", false) && Module::HasActive()) return Module::PathURL("", $url);
        return $url;
    }

    public function addParameterName(string $name) : void
    {
        if (!in_array($name, $this->parameter_names)) {
            $this->parameter_names[] = $name;
        }
    }

    public function getParameterNames() : array
    {
        return $this->parameter_names;
    }
}