<?php
include_once("pages/HTMLPage.php");
include_once("responders/RequestController.php");
include_once("components/renderers/IHeadContents.php");
include_once("components/renderers/IPageComponent.php");
include_once("beans/ConfigBean.php");

include_once("dialogs/MessageDialog.php");
include_once("components/ImagePopup.php");
include_once("utils/IActionCollection.php");
include_once("utils/ActionCollection.php");
include_once("utils/FBPixel.php");
include_once("utils/GTAG.php");
include_once("utils/GTAGObject.php");

class SparkPage extends HTMLPage implements IActionCollection
{

    private static $instance = NULL;

    public static function Instance()
    {
        return self::$instance;
    }

    /**
     * Require auth success to access the page
     * @var bool
     */
    protected $authorized_access = FALSE;

    /**
     * Login page redirection on auth fail
     * @var string
     */
    protected $loginURL = "";

    /**
     * Authenticator to use
     * @var Authenticator
     */
    protected $auth = NULL;

    /**
     * Authenticated context data array. is null if not authenticated yet
     * @var AuthContext
     */
    protected $context = NULL;

    /**
     * The Preferred title of this page for rendering into the <TITLE></TITLE> tag
     */
    protected $preferred_title = "";


    /**
     * @var array IHeadContents
     */
    protected $page_components = array();

    /**
     * @var array IPageComponent
     */
    protected $head_components = array();

    protected $opengraph_tags = array();

    /**
     * Meta tag 'Description' overload. If not empty is used instead of ConfigBean 'seo' section value
     */
    public $description = "";

    /**
     * Meta tag 'Keywords' overload. If not empty is used instead of ConfigBean 'seo' section value
     */
    public $keywords = "";


    /**
     * @var ActionCollection
     */
    protected $actions;

    /**
     * @var FBPixel|null
     */
    protected $fbpixel;

    protected $gtag_objects = array();

    protected $canonical_enabled = false;

    /**
     * Array holding the url parameter names that will be present in the canonical url version of 'this' page
     * @var array
     */
    protected $canonical_params = array();

    public function addGTAGObject(GTAGObject $obj)
    {
        $this->gtag_objects[] = $obj;
    }

    public function getFacebookPixel(): ?FBPixel
    {
        return $this->fbpixel;
    }

    /**
     * @return int The numeric ID as from the Authenticator
     */
    public function getUserID()
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

    public function addURLParameter(URLParameter $parameter)
    {
        //TODO:
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


    /**
     * Render all meta tags for the HEAD section of the page
     */
    protected function renderMetaTags()
    {
        parent::renderMetaTags();

        foreach ($this->opengraph_tags as $tag_name => $tag_content) {
            echo "<meta property='og:$tag_name' content='" . attributeValue($tag_content) . "' />\n";
        }

        echo "<link rel='shortcut icon' href='//" . SITE_DOMAIN . LOCAL."/favicon.ico'>";
        echo "\n";
    }

    /**
     * Finish rendering of the HEAD section
     * This overload enables the google analytics script to be read from the config section from DB
     */
    protected function headEnd()
    {
        if (DB_ENABLED) {
            $config = ConfigBean::Factory();
            $config->setSection("seo");

            $gtag = new GTAG();

            $googleID_analytics = $config->get("googleID_analytics");
            if ($googleID_analytics) {
                $gtag->setID($googleID_analytics);
                echo $gtag->script();
            }

            $googleID_ads = $config->get("googleID_ads");
            if ($googleID_ads) {
                $gtag->setID($googleID_ads);
                echo $gtag->script();
            }
        }

        if ($this->fbpixel) {
            echo $this->fbpixel->script();
        }

        foreach ($this->gtag_objects as $idx => $object) {
            if (!($object instanceof GTAGObject)) continue;
            echo $object->script();
        }

        if ($this->canonical_enabled) {
            $builder = $this->getURL();
            $url_parameters = $builder->getParameterNames();
            foreach ($url_parameters as $idx=>$parameter_name) {
                if (in_array($parameter_name, $this->canonical_params)) continue;
                $builder->remove($parameter_name);
            }
            $canonical_href = fullURL($builder->url());
            echo "<link rel='canonical' href='$canonical_href' />";
        }

        $url = fullURL($this->getURL()->url());
        //X-default tags are recommended, but not mandatory
        echo "<link rel='alternate' hreflang='x-default' href='$url'/>";

        echo "<link rel='alternate' hreflang='".DEFAULT_LOCALE."' href='$url'/>";

        parent::headEnd();
    }

    public function addComponent(Component $cmp)
    {
        if ($cmp instanceof IPageComponent) {
            $key = $cmp->hash();
            $found = array_key_exists($key, $this->page_components);
            if (!$found) $this->page_components[$key] = $cmp;
        }

        if ($cmp instanceof IHeadContents) {
            $key = $cmp->hash();
            $found = array_key_exists($key, $this->head_components);
            if (!$found) $this->head_components[$key] = $cmp;
        }
    }

    protected function renderJS()
    {
        ?>
        <!-- SparkPage local script start -->
        <script type='text/javascript'>
            let LOCAL = "<?php echo LOCAL;?>";
            let SPARK_LOCAL = "<?php echo SPARK_LOCAL;?>";
            let STORAGE_LOCAL = "<?php echo STORAGE_LOCAL;?>";
        </script>
        <!-- SparkPage local script end -->
        <?php

        parent::renderJS();
    }

    /**
     * SparkPage constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        self::$instance = $this;

        $this->addMeta("revisit-after", "1 days");
        $this->addMeta("robots", "index, follow");
        $this->addMeta("keywords", "%meta_keywords%");
        $this->addMeta("description", "%meta_description%");

        $this->addMeta("viewport", "width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=yes");

        $this->addCSS(SPARK_LOCAL . "/css/ModalPane.css");
        $this->addCSS(SPARK_LOCAL . "/css/ImagePopup.css");
        $this->addCSS(SPARK_LOCAL . "/css/MessageDialog.css");
        $this->addCSS(SPARK_LOCAL . "/css/SparkPage.css");

        $this->addJS(SPARK_LOCAL . "/js/utils.js");
        $this->addJS(SPARK_LOCAL . "/js/jquery-3.7.1.min.js");

        $this->addJS(SPARK_LOCAL . "/js/js.cookie.min.js");

        $this->addJS(SPARK_LOCAL . "/js/CallStack.js");

        $this->addJS(SPARK_LOCAL . "/js/SparkObject.js");
        $this->addJS(SPARK_LOCAL . "/js/SparkEvent.js");
        $this->addJS(SPARK_LOCAL . "/js/Component.js");

        $this->addJS(SPARK_LOCAL . "/js/JSONRequest.js");

        $this->addJS(SPARK_LOCAL . "/js/ModalPopup.js");
        $this->addJS(SPARK_LOCAL . "/js/Tooltip.js");
        $this->addJS(SPARK_LOCAL . "/js/ImagePopup.js");
        $this->addJS(SPARK_LOCAL . "/js/dialogs/MessageDialog.js");

        $this->addJS(SPARK_LOCAL . "/js/SparkPage.js");

        $this->actions = new ActionCollection();

        $dialog = new MessageDialog();

        if (DB_ENABLED) {
            $config = ConfigBean::Factory();
            $config->setSection("seo");

            $facebookID_pixel = $config->get("facebookID_pixel");
            if ($facebookID_pixel) {
                $this->fbpixel = new FBPixel($facebookID_pixel);
            }

            $adsID = $config->get("googleID_ads", "");
            $conversionID = $config->get("googleID_ads_conversion", "");
            if ($adsID && $conversionID) {
                $obj = new GTAGObject();
                $obj->setCommand(GTAGObject::COMMAND_EVENT);
                $obj->setType("conversion");
                $obj->setParamTemplate("{'send_to': '%googleID_ads_conversion%'}");
                $obj->setName("googleID_ads_conversion");
                $data = array("googleID_ads_conversion"=>$conversionID);
                $obj->setData($data);
                $this->addGTAGObject($obj);
            }

        }
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
                        Session::Set("login.redirect", $this->getPageURL());

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
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->preferred_title = $title;
    }

    /**
     * Get the contents of the TITLE tag
     * @return string
     */
    public function getTitle(): string
    {
        return $this->preferred_title;
    }

    public function obCallback(string &$buffer)
    {
        $title = $this->preferred_title;

        $meta_keywords = "";
        $meta_description = "";

        if (DB_ENABLED) {
            $config = ConfigBean::Factory();
            $config->setSection("seo");

            $meta_keywords = sanitizeKeywords($config->get("meta_keywords"));
            $meta_description = $config->get("meta_description");
        }

        if ($this->keywords) {
            $meta_keywords = $this->keywords;
        }

        if ($this->description) {
            $meta_description = $this->description;
        }

        $replace = array("%title%"            => strip_tags($title), "%meta_keywords%" => prepareMeta($meta_keywords),
                         "%meta_description%" => prepareMeta($meta_description));

        $buffer = strtr($buffer, $replace);

    }

    /**
     * Start rendering of this page
     *
     * 1. RequestController processes all Ajax responders attached to this page
     * 2. Config section of DB is read to load meta_keywords/description
     * 3. Output buffering is set up
     * 4. All tags including the BODY tag are rendered to the output
     * 5. RequestController processes all regular responders(non-ajax)
     */
    public function startRender()
    {

        if ($this->getURL()->contains("JSONRequest")) {
            //will 'exit' script always as JSONRequest is found as request URL parameter
            debug("Handling JSONRequest");
            RequestController::processJSONResponders();
        }

        ob_start();

        foreach ($this->head_components as $idx => $cmp) {
            $css_files = $cmp->requiredStyle();
            if (!is_array($css_files)) {
                echo $css_files;
            }
            foreach ($css_files as $key => $url) {
                $this->addCSS($url, get_class($cmp), TRUE);
            }
            $js_files = $cmp->requiredScript();
            if (!is_array($js_files)) {
                echo $js_files;
            }
            else {
                foreach ($js_files as $key => $url) {
                    //no prepend here
                    $this->addJS($url, get_class($cmp), FALSE);
                }
            }
        }

        parent::startRender();

        //regular responders to commands
        RequestController::processResponders();
    }

    /**
     * Finalize rendering of this page
     *
     * 1. Process messages if any
     * 2. Process final components rendering ie all before the closing BODY tag
     * 3. Render the closing BODY tag
     * 4. Render the closing HTML tag
     * 5. End output buffering and send to client
     */
    public function finishRender()
    {
        $this->renderPageComponents();

        $this->processMessages();

        parent::finishRender();

        $buffer = ob_get_contents();
        ob_end_clean();
        $this->obCallback($buffer);
        echo $buffer;
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
