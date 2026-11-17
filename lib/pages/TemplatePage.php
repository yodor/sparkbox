<?php
include_once("templates/Template.php");
include_once("templates/TemplateConfig.php");

/**
 * The module template handling of a page: the module path from the request, its configuration
 * (Template::PathConfig), the content (Template::LoadContent) and update() when the content processed its input.
 *
 * Any SparkPage subclass can host a module with it - SparkTemplatePage (admin), StoreTemplatePage (account,
 * checkout), a site page class. The class:
 *  - implements IObserver,
 *  - calls templateConstruct() from its constructor (after parent::__construct()),
 *  - implements update(TemplateContent $content) - puts the content into the page.
 */
trait TemplatePage
{
    /**
     * The module path of this request ("home" for the module folder itself)
     * @var string
     */
    protected string $path = "home";

    /**
     * The path as a body css class ('orders/details' → 'orders_details')
     * @var bool
     */
    protected bool $pathBodyClass = false;

    /**
     * The address of this page set by its content (a page with its own URL - /pages/<id>/<slug>/): canonical and
     * language links use it
     * @var URL|null
     */
    protected ?URL $contentURL = null;

    /**
     * Register for the template events and read the path parameter
     * @return void
     */
    protected function templateConstruct() : void
    {
        SparkEventManager::register(TemplateEvent::class, $this);
        SparkEventManager::register(TemplateConfigEvent::class, $this);

        $this->addParameterName("path");
        //the module folder itself is the 'home' path
        if (isset($_GET["path"]) && trim((string)$_GET["path"], "/") !== "") {
            $this->path = Template::FormatPath((string)$_GET["path"], "/", true);
        }
    }

    /**
     * The module path of this request ('/orders/details')
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Load the configuration of the path: Template::PathConfig - its content is loaded by onEvent().
     * A failed configuration shows the error page (the trace only with debug enabled).
     * @return void
     */
    public function initialize() : void
    {
        try {
            Template::PathConfig($this->path);
            $this->body->setAttribute("path", $this->path);
            if ($this->pathBodyClass) {
                $this->body->addClassName(Template::FormatPath($this->path, "_", false));
            }
        }
        catch (TemplatePathNotFound $e) {
            Debug::ErrorLog("PathConfig: " . $e->getMessage());
            http_response_code(404);
            $this->head()->addMeta("robots", "noindex");
            Template::NotFoundConfig();
        }
        catch (Exception $e) {
            Debug::ErrorLog("PathConfig failed: " . $e->getMessage());
            $message = $e->getMessage();
            if (Debug::IsEnabled()) $message .= " - " . $e->getTraceAsString();
            Template::ErrorConfig("Error:{$this->path}", $message);
        }
    }

    /**
     * Template::LoadContent() when the path config is ready, update() when the content processed its input
     * @param SparkEvent $event
     * @return void
     * @throws Exception
     */
    public function onEvent(SparkEvent $event) : void
    {
        if ($event->isEvent(TemplateConfigEvent::UPDATE)) {
            Template::LoadContent();
        }
        else if ($event->isEvent(TemplateEvent::CONTENT_INPUT_PROCESSED)) {
            $content = $event->getSource();
            if (!($content instanceof TemplateContent)) throw new Exception("Incorrect event source - expecting TemplateContent");
            $this->update($content);
        }
    }

    /**
     * Put the content into the page - after the content processed its input
     * @param TemplateContent $content
     * @return void
     */
    abstract public function update(TemplateContent $content) : void;

    /**
     * The address of this page when the content has its own URL (setContentURL()), otherwise the current URL as
     * a module path URL
     * @return URL
     */
    public function currentURL() : URL
    {
        if ($this->contentURL instanceof URL) return clone $this->contentURL;
        return Module::PathURL("", parent::currentURL());
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
}
