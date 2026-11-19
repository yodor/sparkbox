<?php
include_once("responders/json/JSONResponder.php");
include_once("templates/TemplateContent.php");
include_once("pages/HTMLHead.php");
include_once("pages/HTMLBody.php");
include_once("objects/SparkEventManager.php");
include_once("objects/events/ComponentEvent.php");

/**
 * The content of a module path as a fragment - without the frame. The module engine creates it when the request
 * asks for it (a JSON request: ?JSONRequest=1&responder=ContentFragmentResponder&function=render) and for every
 * request of a module without a frame (pageClass empty: the HTML answer is the fragment itself).
 *
 * The JSON response:
 *  - contents: the HTML of the content component (one element)
 *  - styles:   the stylesheets the components need (the page head), in their order
 *  - scripts:  the script files the components need
 *  - page:     the page components (dialog templates, inline scripts) - the frame renders them at the end of the body
 * JSONComponent (sparkfront/js/JSONComponent.js) loads the missing styles and scripts, then appends the contents.
 */
class ContentFragmentResponder extends JSONResponder
{
    protected TemplateContent $content;

    public function __construct(TemplateContent $content)
    {
        parent::__construct();
        $this->content = $content;
    }

    /**
     * True when this request asks for the content of the path as a fragment
     * @return bool
     */
    public static function IsRequested() : bool
    {
        return Spark::isJSONRequest() && Spark::strcmp_isset(RequestResponder::KEY_COMMAND, ContentFragmentResponder::class, $_REQUEST);
    }

    /**
     * The head and the body collect what the components need (their css / js, the page components) - for the JSON
     * requests the boot does not register them (sparkbox.php)
     * @return void
     */
    public static function CollectHead() : void
    {
        if (!Spark::isJSONRequest()) return;
        static $done = false;
        if ($done) return;
        $done = true;
        SparkEventManager::register(ComponentEvent::class, HTMLHead::Instance());
        SparkEventManager::register(ComponentEvent::class, HTMLBody::Instance());
    }

    public function _render(JSONResponse $response) : void
    {
        $this->content->component()->render();

        $response->styles = HTMLHead::Instance()->stylesheetURLs();
        $response->scripts = HTMLHead::Instance()->scriptURLs();

        ob_start();
        HTMLBody::Instance()->renderPageComponents();
        $response->page = (string)ob_get_clean();
    }

    /**
     * The HTML answer of a module without a frame: the stylesheets and scripts the components need, the content,
     * the page components
     * @param TemplateContent $content
     * @return void
     */
    public static function RenderHTML(TemplateContent $content) : void
    {
        header("Content-Type: text/html; charset=utf-8");
        foreach (HTMLHead::Instance()->stylesheetURLs() as $href) {
            echo "<link rel='stylesheet' href='" . Spark::AttributeValue($href) . "'>\n";
        }
        foreach (HTMLHead::Instance()->scriptURLs() as $src) {
            echo "<script src='" . Spark::AttributeValue($src) . "'></script>\n";
        }
        $content->component()->render();
        HTMLBody::Instance()->renderPageComponents();
    }
}
