<?php
include_once("storage/CacheFactory.php");
include_once("pages/HTMLPage.php");
include_once("responders/RequestController.php");
include_once("objects/SparkEventManager.php");

include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("components/Template.php");

include_once("beans/ConfigBean.php");

include_once("dialogs/MessageDialog.php");
include_once("components/ImagePopup.php");
include_once("utils/IActionCollection.php");
include_once("objects/ActionCollection.php");
include_once("utils/script/FBPixel.php");
include_once("utils/script/GTAG.php");
include_once("objects/data/GTAGObject.php");

class SparkLocationScript extends PageScript
{
    public function code() : string
    {
        $page_local = new Script();
        $local = LOCAL;
        $spark_local = SPARK_LOCAL;
        $storage_local = STORAGE_LOCAL;
        return <<<JS
        const LOCAL = "{$local}";
        const SPARK_LOCAL = "{$spark_local}";
        const STORAGE_LOCAL = "{$storage_local}";
JS;

    }

}

class SparkPage extends HTMLPage implements IActionCollection
{

    private static ?SparkPage $instance = NULL;

    public static function Instance() : ?SparkPage
    {
        return self::$instance;
    }

    /**
     * Require auth success to access the page
     * @var bool
     */
    protected bool $authorized_access = FALSE;

    /**
     * Login page redirection on auth fail
     * @var string
     */
    protected string $loginURL = "";


    /**
     * Authenticator to use during authorization
     * @var Authenticator|null
     */
    protected ?Authenticator $auth = NULL;

    /**
     * Authenticated context data array. is null if not authenticated yet
     * @var AuthContext|null
     */
    protected ?AuthContext $context = NULL;

    /**
     * The Preferred title of this page for rendering into the <TITLE></TITLE> tag
     */
    protected string $preferred_title = "";


    /**
     * @var array IPageComponent
     */
    protected array $page_components = array();


    /**
     * Meta tag 'Description' overload. If not empty is used instead of ConfigBean 'seo' section value
     */
    protected string $description = "";

    /**
     * Meta tag 'Keywords' overload. If not empty is used instead of ConfigBean 'seo' section value
     */
    protected string $keywords = "";


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


    public function setMetaKeywords(string $keywords) : void
    {
        $this->keywords = $keywords;
    }

    public function setMetaDescription(string $description) : void
    {
        $this->description = $description;
    }

    /**
     * @return int The numeric ID as from the Authenticator
     */
    public function getUserID() : int
    {
        if ($this->context instanceof AuthContext) {
            return $this->context->getID();
        }
        return -1;
    }

    public function getActions(): ActionCollection
    {
        return $this->actions;
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    /**
     * Handle component event COMPONENT_CREATED
     * Add required CSS and JS files to the head for components implementing IHeadContents
     * Add component to page_components if implementing IPageComponent
     *
     * @param SparkEvent $event
     * @return void
     */
    protected function componentEvent(SparkEvent $event) : void
    {
        if (!($event instanceof ComponentEvent)) return;
        if (!$event->isEvent(ComponentEvent::COMPONENT_CREATED)) return;

        $cmp = $event->getSource();

        if ($cmp instanceof HTMLHead) return;
        if ($cmp instanceof HTMLPage) return;
        if ($cmp instanceof HTMLBody) return;

        if ($cmp instanceof IPageComponent) {
            if ($cmp instanceof IPageScript) {
                $this->page_components[] = $cmp;
            }
            else {
                if ($cmp instanceof ITemplate) {
                    $template = new Template();
                    $template->setID($cmp->templateID());
                    $template->items()->append($cmp);
                    $this->page_components[$template->getID()] = $template;
                }
                else {
                    $this->page_components[get_class($cmp)] = $cmp;
                }
            }
        }

        //SparkPage constructors add css files directly to the head section
        //use prepend to allow override of component defined styles
        if ($this->head()) {
            if ($cmp instanceof IHeadContents) {
                $css_files = $cmp->requiredStyle();
                foreach ($css_files as $key => $url) {
                    $this->head()->addCSS($url, true);
                }

                $js_files = $cmp->requiredScript();
                foreach ($js_files as $key => $url) {
                    //no prepend here
                    $this->head()->addJS($url);
                }
            }
        }
    }


    /**
     * Set the default static instance
     * Sets default css / js
     * @throws Exception
     */
    public function __construct()
    {
        debug("--- CTOR ---");

        SparkEventManager::register(ComponentEvent::class, new SparkObserver($this->componentEvent(...)));
        parent::__construct();

        self::$instance = $this;



        $this->head()->addMeta("viewport", "width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=yes");

        $this->head()->addCSS(SPARK_LOCAL . "/css/SparkPage.css");

//        $this->head()->addJS(SPARK_LOCAL . "/js/jquery-3.7.1.min.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/js.cookie.min.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/SparkObject.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/SparkEvent.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/CallStack.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/TemplateFactory.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/Component.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/TemplateComponent.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/JSONRequest.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/JSONComponent.js");


        $this->head()->addJS(SPARK_LOCAL . "/js/Tooltip.js");
;

        $location = new SparkLocationScript();

        $this->actions = new ActionCollection();

        //default template for showAlert
        $dialog = new MessageDialog();

    }

    /**
     * Authorize this page usage if this->auth assigned with Authenticator prior to this method call
     *
     * Assign AuthContext to $this->context on successful authorization
     * If $this->authorized_access is true but authorization failed
     *  send JSONResponse with message if this is JSONRequest request and exits script execution
     *  redirects to the loginURL if it is set and exits script execution
     *  else  throws Exception
     * @return void
     * @throws Exception
     */
    public function authorize(): void
    {
        if (!($this->auth instanceof Authenticator)) return;

        debug("Using Authenticator: " . get_class($this->auth));

        $this->context = $this->auth->authorize();

        if ($this->context instanceof AuthContext) {

            debug("Authorization success");
            return;
        }

        debug("Authorization failed");

        if (!$this->authorized_access) {
            debug("Authorization is not required");
            return;
        }

        debug("Authorization is required for this page");

        if (isset($_GET[JSONResponder::KEY_JSONREQUEST])) {
            $response = new JSONResponse("RequestController");
            $message = tr("Your session has expired.");
            $message.= "<BR>";
            $message.= tr("Please log into your profile again.");
            $message.= "<BR>";
            $response->message = $message;
            $response->send();
            exit;
        }

        if (strlen($this->loginURL) > 0) {

            debug("Redirecting to login page URL: " . $this->loginURL);
            header("Location: $this->loginURL");
            exit;
        }

        throw new Exception("Authorization failed");
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
     * Override to set the page title
     * Final place to set the contents of $this->preferred_title before head output is started
     * @return void
     */
    protected function constructTitle() : void
    {

    }

    /**
     * Assign the title, keywords and description to the head section
     * Process the meta values to max 150 symbols
     * @return void
     */
    protected function prepareMetaTitle() : void
    {
        $title = strip_tags($this->preferred_title);
        $this->head()->setTitle($title);
        $this->head()->addOGTag("title", $title);

        $meta_keywords = prepareMeta($this->keywords, 150);
        $meta_description = prepareMeta($this->description, 150);
        $this->head()->addMeta("keywords", $meta_keywords);
        $this->head()->addMeta("description", $meta_description);
        $this->head()->addOGTag("description", $meta_description);
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

        //JSONReponders exit execution
        //can create IPageComponents
        //can set header to redirect
        RequestController::process();

        //prepare $this->preferred_title value
        $this->constructTitle();

        //apply title, meta keywords, meta description to the head
        $this->prepareMetaTitle();

        parent::startRender();
        //<body> is in output buffer now

        //show session alerts - !will start session
        $this->processMessages();

        //push head until including the body tag - browser can fetch css and scripts while we do the body contents
        ob_end_flush();

        //disable session work from here on

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

        //append dialog templates and pagescripts
        $this->renderPageComponents();

        parent::finishRender();
        //</html> ended here

        ob_end_flush();

        if (PAGE_CACHE_ENABLED) {
            register_shutdown_function(function(){
                CacheFactory::CleanupPageCache();
            });
        }
    }

    /**
     * Render all component implementing the IFinalRenderer before closing the BODY
     */
    protected function renderPageComponents() : void
    {
        foreach ($this->page_components as $idx => $cmp) {
            $cmp->render();
        }
    }

    /**
     * Show message as a popup if Session "alert" key is set
     * Clears the Session "alert" key
     */
    protected function processMessages()
    {
        $alert = Session::GetAlert();
        if ($alert) {
            ?>
            <script type='text/javascript'>
                onPageLoad(function () {
                    showAlert(<?php echo json_encode($alert);?>);
                });
            </script>
            <?php
        }
        Session::ClearAlert();
    }

    /**
     * Return current working URL for building page parameters.
     * Default implementation return URL::Current.
     * @return URL
     */
    public function currentURL() : URL
    {
        return URL::Current();
    }

}

?>
