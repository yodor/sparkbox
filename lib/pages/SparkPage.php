<?php
include_once("pages/HTMLPage.php");
include_once("responders/RequestController.php");
include_once("objects/SparkEventManager.php");

include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");

include_once("beans/ConfigBean.php");

include_once("dialogs/MessageDialog.php");
include_once("components/ImagePopup.php");
include_once("utils/IActionCollection.php");
include_once("objects/ActionCollection.php");
include_once("utils/output/FBPixel.php");
include_once("utils/output/GTAG.php");
include_once("objects/data/GTAGObject.php");


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

    public function setActions(ActionCollection $actions)
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

        if ($cmp instanceof IPageComponent) {
            $this->page_components[] = $cmp;
        }

        //SparkPage constructors add css files directly to the head section
        //use prepend to allow overload of component defined styles
        if ($cmp instanceof IHeadContents) {
            $css_files = $cmp->requiredStyle();
            foreach ($css_files as $key => $url) {
                $this->head()->addCSS($url, "", true);
            }

            $js_files = $cmp->requiredScript();
            foreach ($js_files as $key => $url) {
                //no prepend here
                $this->head()->addJS($url, "", FALSE);
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

        $this->head()->addMeta("revisit-after", "1 days");
        $this->head()->addMeta("robots", "index, follow");
        $this->head()->addMeta("keywords", "%meta_keywords%");
        $this->head()->addMeta("description", "%meta_description%");

        $this->head()->addMeta("viewport", "width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=yes");

        $this->head()->addCSS(SPARK_LOCAL . "/css/ModalPane.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/ImagePopup.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/MessageDialog.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/SparkPage.css");

        $this->head()->addJS(SPARK_LOCAL . "/js/utils.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/jquery-3.7.1.min.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/js.cookie.min.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/CallStack.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/SparkObject.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/SparkEvent.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/Component.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/JSONRequest.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/ModalPopup.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/Tooltip.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/ImagePopup.js");
        $this->head()->addJS(SPARK_LOCAL . "/js/dialogs/MessageDialog.js");

        $this->head()->addJS(SPARK_LOCAL . "/js/SparkPage.js");

        $this->actions = new ActionCollection();

        $dialog = new MessageDialog();

    }

    public function authorize()
    {
        if ($this->auth) {

            debug("Using Authenticator: " . get_class($this->auth));

            $this->context = $this->auth->authorize();

            if ($this->context) {

                debug("Authorization success");

            }
            else {

                debug("Authorization failed");

                if ($this->authorized_access) {

                    debug("'authorized_access' is set for this page");

                    if (isset($_GET["ajax"]) || isset($_GET["JSONRequest"])) {
                        $response = new JSONResponse("RequestController");
                        $message = tr("Your session has expired");
                        $message.= "<BR>";
                        $message.= tr("Please log into your profile again");
                        $message.= "<BR>";
                        $response->message = $message;
                        $response->send();
                        exit;
                    }

                    if (strlen($this->loginURL) > 0) {

                        debug("Redirecting to login page URL: " . $this->loginURL);
                        Session::Set("login.redirect", URL::Current()->toString());

                        header("Location: $this->loginURL");
                        exit;
                    }

                    throw new Exception("Authorization failed");
                }
                else {
                    debug("'authorized_access' is NOT set");
                }

            }

        }
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
    public function startRender()
    {
        debug("--- Head Buffer Started ---");

        //head output buffer
        ob_start(null, 4096);

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

        //push head until including the body tag - browser can fetch css and scripts while we do the body contents
        ob_end_flush();
        debug("--- Head Buffer Sent ---");
        //first output to client - no session start further below - headers sent

        debug("--- Page Buffer Start ---");
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
    public function finishRender()
    {
        //still inside the body section

        debug("--- FinishRender ---");
        //append message dialog templates
        $this->renderPageComponents();

        //show session alerts
        $this->processMessages();


        parent::finishRender();
        //</html> ended here

        ob_end_flush();
        debug("--- Page Buffer Sent ---");


        if (PAGE_CACHE_ENABLED) {
            register_shutdown_function(function(){
                CacheEntry::CleanupPageCache();
            });
        }
    }

    /**
     * Render all component implementing the IFinalRenderer before closing the BODY
     */
    protected function renderPageComponents()
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

}

?>
