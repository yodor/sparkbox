<?php
include_once("lib/pages/HTMLPage.php");
include_once("lib/handlers/RequestController.php");
include_once("lib/components/renderers/IHeadContents.php");
include_once("lib/components/renderers/IPageComponent.php");
include_once("lib/beans/ConfigBean.php");

include_once("lib/panels/MessageDialog.php");

class SparkPage extends HTMLPage
{

    /**
     * @var Authenticator
     */
    protected $auth = NULL;
    /**
     * Authenticated context data array. is null if not authenticated yet
     * @var AuthContext
     */
    protected $context = NULL;

    /**
     * The Preferred title of this page for rendering into the <TITLE></TITLE>
     */
    protected $preferred_title = "";

    /**
     * @var string
     */
    protected $page_title = "";

    /**
     * @var string
     */
    protected $caption = "";

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
     * Meta tag 'Description' overload. If not empty is used instead of $config_description
     */
    public $description = "";

    /**
     * Meta tag 'Keywords' overload. If not empty is used instead of $config_keywords
     */
    public $keywords = "";

    /**
     * property used to connect the current page with menus
     */
    protected $accessible_title = "";

    protected $accessible_parent = "";


    /**
     * property array of Action objects holding page action buttons
     */
    protected $actions = array();

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

    public function addAction(Action $action)
    {
        $this->actions[$action->getAttribute("action")] = $action;
    }

    public function getAction($action_name)
    {
        if (isset($this->actions[$action_name])) {
            return $this->actions[$action_name];
        }
        return NULL;
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function addOGTag($tag_name, $tag_content)
    {
        $this->opengraph_tags[$tag_name] = $tag_content;
    }

    /**
     * gets the accessible title
     *
     * @return string the accessible title of this page object. Default none.
     */
    public function getAccessibleTitle()
    {
        return $this->accessible_title;
    }

    /**
     * sets the accessible title of this page
     *
     * @param $menu_title string Tht title to set
     *
     */
    public function setAccessibleTitle($menu_title)
    {
        $this->accessible_title = $menu_title;
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

        echo "<link rel='shortcut icon' href='//" . SITE_DOMAIN . "/favicon.ico'>";
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

            $google_analytics = $config->getValue("google_analytics");
            if ($google_analytics) {

                echo "<script type='text/javascript'>\n";
                $google_analytics = mysql_real_unescape_string($google_analytics);
                $google_analytics = str_replace("\r", "", $google_analytics);
                $google_analytics = str_replace("\n", "", $google_analytics);
                echo $google_analytics;
                echo "\n";
                echo "</script>\n";
            }
        }
        parent::headEnd();
    }

    public function addComponent(Component $cmp)
    {
        if ($cmp instanceof IPageComponent) {
            $this->page_components[] = $cmp;
        }

        if ($cmp instanceof IHeadContents) {
            $this->head_components[] = $cmp;
        }

    }

    /**
     * Sets the value of the $caption property
     * @param string $caption
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    }

    /**
     * Gets the value of the $caption property
     * @return string
     */
    public function getCaption() : string
    {
        return $this->caption;
    }


    protected function renderJS()
    {
        parent::renderJS();
        global $left, $right;

        ?>
        <script type='text/javascript'>
            let SITE_ROOT = "<?php echo SITE_ROOT;?>";
            let ajax_loader = "<div class='AjaxLoader'></div>";
            let ajax_loader_src = SITE_ROOT + "lib/images/ajax-loader.gif";
            let left = "<?php echo $left;?>";
            let right = "<?php echo $right;?>";
        </script>
        <?php
    }

    public function __construct(Authenticator $auth = NULL, string $loginURL = "")
    {

        parent::__construct();

        if ($auth) {
            $this->auth = $auth;
        }

        if ($this->auth) {
            debug("Using Authenticator: " . get_class($this->auth));

            $this->context = $this->auth->authorize();

            if ($this->context) {

                debug("Authorization success");

            }
            else {

                debug("Authorization failed");

                if (isset($_GET["ajax"])) {
                    throw new Exception("Your session is expired");
                }

                if (strlen($loginURL) > 0) {
                    header("Location: $loginURL");
                    exit;
                }

                throw new Exception("Authorization failed");
                //redirect routines

            }

        }


        $this->addMeta("revisit-after", "1 days");
        $this->addMeta("robots", "index, follow");
        $this->addMeta("keywords", "%meta_keywords%");
        $this->addMeta("description", "%meta_description%");

        $this->addCSS(SITE_ROOT . "lib/css/popups.css");
        $this->addCSS(SITE_ROOT . "lib/css/SparkPage.css");

        $this->addJS(SITE_ROOT . "lib/js/jquery-1.8.0.min.js");
        $this->addJS(SITE_ROOT . "lib/js/utils.js");
        $this->addJS(SITE_ROOT . "lib/js/JSONRequest.js");
        $this->addJS(SITE_ROOT . "lib/js/tooltip.js");
        $this->addJS(SITE_ROOT . "lib/js/ModalPopup.js");

        $dialog = new MessageDialog();
    }


    public function setPreferredTitle(string $page_title)
    {
        $this->preferred_title = $page_title;
    }

    public function getPreferredTitle() : string
    {
        return $this->preferred_title;
    }

    public function obCallback(string $buffer)
    {

        $title = $this->preferred_title . TITLE_PATH_SEPARATOR . SITE_TITLE;

        $buffer = preg_replace('#(<title.*?>).*?(</title>)#', "<title>" . strip_tags($title) . "</title>", $buffer);

        $meta_keywords = "";
        $meta_description = "";

        try {
            if (DB_ENABLED) {
                $config = ConfigBean::Factory();
                $config->setSection("seo");

                $meta_keywords = $config->getValue("meta_keywords");
                $meta_description = $config->getValue("meta_description");
            }
        }
        catch (Exception $e) {
            //
        }

        if ($this->keywords) {
            $meta_keywords = $this->keywords;
        }

        if ($this->description) {
            $meta_description = $this->description;
        }


        $buffer = str_replace("%meta_keywords%", strip_tags($meta_keywords), $buffer);
        $buffer = str_replace("%meta_description%", strip_tags($meta_description), $buffer);

        return $buffer;
    }


    /**
     * Start rendering of this page
     *
     * 1. RequestController processes all Ajax handlers attached to this page
     * 2. Config section of DB is read to load meta_keywords/description
     * 3. Output buffering is set up
     * 4. All tags including the BODY tag are rendered to the output
     * 5. RequestController processes all regular handlers(non-ajax)
     */
    public function startRender()
    {
        RequestController::processAjaxHandlers();
        
        ob_start(array($this, 'obCallback'));

        foreach ($this->head_components as $idx => $cmp) {
            $css_files = $cmp->requiredStyle();
            foreach ($css_files as $key => $url) {
                $this->addCSS($url, get_class($cmp), true);
            }
            $js_files = $cmp->requiredScript();
            foreach ($js_files as $key => $url) {
                //no prepend here
                $this->addJS($url, get_class($cmp), false);
            }
        }

        parent::startRender();

        RequestController::processRequestHandlers();
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

        ob_end_flush();
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

            $alert = json_encode($alert);
            ?>
            <script type='text/javascript'>
                let alert = <?php echo $alert;?>;
                onPageLoad(function () {
                    showAlert(alert);
                });
            </script>
            <?php

        }

        Session::SetAlert("");

    }

}

?>
