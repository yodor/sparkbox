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
     * Meta tag 'Description' as read from config table from DB
     */
    protected $config_description = "";

    /**
     * Meta tag 'Keywords' as read from config table from DB
     */
    protected $config_keywords = "";

    /**
     * property used to connect the current page with menus
     */
    protected $accessible_title = "";

    protected $accessible_parent = "";

    /**
     * property array of key=>value strings used to render all meta tags of this page
     */
    protected $meta = array();

    /**
     * property array of Action objects holding page action buttons
     */
    protected $actions = array();

    /**
     * property
     * array of Strings representing URL of all css files used
     */
    protected $css_files = array();

    /**
     * property
     * array of Strings representing URLs of all JavaScript that are used in this page
     */
    protected $js_files = array();


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

    /**
     *  Add meta tag to be rendered into this page.
     * @param $name string The name attribute to add to the Meta collection
     * @param $content string The content attribute
     */
    public function addMeta($name, $content)
    {
        $this->meta[$name] = $content;
    }

    /**
     *  Get the content attribute of the meta
     * @param $name string The name attribute to
     * @return string The content attribute as set to the $name
     */
    public function getMeta($name)
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : "";
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
    protected function dumpMetaTags()
    {
        parent::dumpMetaTags();

        echo '<meta name="revisit-after" content="1 days">';
        echo "\n";

        echo '<meta name="robots" content="index, follow">';
        echo "\n";

        echo "<meta name='keywords' content='%meta_keywords%'>";
        echo "\n";

        echo "<meta name='description' content='%meta_description%'>";
        echo "\n";

        foreach ($this->opengraph_tags as $tag_name => $tag_content) {
            echo "<meta property='og:$tag_name' content='" . attributeValue($tag_content) . "' />\n";
        }

        echo "<link rel='shortcut icon' href='//" . SITE_DOMAIN . "/favicon.ico'>";
        echo "\n";

        foreach ($this->meta as $name => $content) {
            echo "<meta name='" . htmlentities($name) . "' content='" . htmlentities($content) . "'>";
            echo "\n";
        }
    }

    /**
     * Start rendering of the HEAD section
     */
    protected function headStart()
    {
        parent::headStart();
    }

    /**
     * Finish rendering of the HEAD section
     * This overload enables the google analytics script to be read from the config section from DB
     */
    protected function headEnd()
    {
        if (DB_ENABLED) {
            $config = ConfigBean::factory();
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
        if ($cmp instanceof IPageComponent)
        {
            $this->page_components[] = $cmp;
        }

        if ($cmp instanceof IHeadContents)
        {
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
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Render all the CSS script for this page into the HEAD section
     */
    protected function dumpCSS()
    {
        echo "<!-- SparkPage CSS Start -->\n";
        parent::dumpCSS();

        echo "<link rel='stylesheet' href='" . SITE_ROOT . "lib/css/popups.css' type='text/css'>";
        echo "\n";

        //SparkPage default stylesheet
        echo "<link rel='stylesheet' href='" . SITE_ROOT . "lib/css/SparkPage.css' type='text/css'>";
        echo "\n";

        $css_array = array();
        foreach ($this->head_components as $idx => $cmp) {
            //dump used components in page
            //echo "<!-- " . $idx . " -->\n";
            $css_files = $cmp->requiredStyle();
            foreach ($css_files as $key=>$url) {
                $usedBy = array();
                if (isset($css_array[$url])) {
                    $usedBy = $css_array[$url];
                }
                $usedBy[get_class($cmp)] = 1;
                $css_array[$url] = $usedBy;
            }
        }

        echo "<!-- Component CSS Files Start -->\n";
        foreach($css_array as $url=>$usedBy) {

            echo "<link rel='stylesheet' href='$url' type='text/css' >";
            echo "<!-- Used by: ".implode("; ", array_keys($usedBy))." -->\n";
        }
        echo "<!-- Component CSS Files End -->\n";


        echo "<!-- SparkPage CSS Files Start -->";
        echo "\n";
        foreach ($this->css_files as $file => $is_local) {
            $href = $file;
            if ($is_local) {
                $href = SITE_ROOT . "css/" . $file;
            }
            echo "<link rel='stylesheet' href='$href' type='text/css' >";
            echo "\n";
        }
        echo "<!-- SparkPage CSS Files End -->";

        echo "\n";
        echo "<!-- SparkPage CSS End -->\n";
    }

    /**
     * Adds a CSS file to this page CSS scripts collection
     * @param string $filename The filename of the CSS script.
     * @param boolean $is_local Set to true to prepend the filename with /css/ folder
     */
    public function addCSS($filename, $is_local = true)
    {
        $this->css_files[$filename] = $is_local;
    }

    /**
     * Adds a JavaScript file to page JavaScripts collection
     * @param string $filename The filename of the CSS script.
     * @param boolean $is_local Set to true to prepend the filename with /css/ folder
     */
    public function addJS($filename, $is_local = true)
    {
        $this->js_files[$filename] = $is_local;
    }

    /**
     * Render all the JavaScripts
     */
    protected function dumpJS()
    {
        echo "<!-- SparkPage JavaScript Start -->\n";

        parent::dumpJS();
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

        echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/jquery-1.8.0.min.js'></script>";
        echo "\n";

        echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/utils.js'></script>";
        echo "\n";

        echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/JSONRequest.js'></script>";
        echo "\n";

        echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/tooltip.js'></script>";
        echo "\n";

        echo "<script type='text/javascript' src='" . SITE_ROOT . "lib/js/ModalPopup.js'></script>";
        echo "\n";

        //

        $js_array = array();
        foreach ($this->head_components as $idx => $cmp) {
            //dump used components in page
            //echo "<!-- " . $idx . " -->\n";
            $js_files = $cmp->requiredScript();
            if (!is_array($js_files)) {
                echo $js_files;
                continue;
            }
            foreach ($js_files as $key=>$url) {
                $usedBy = array();
                if (isset($js_array[$url])) {
                    $usedBy = $js_array[$url];
                }
                $usedBy[get_class($cmp)] = 1;
                $js_array[$url] = $usedBy;
            }
        }

        echo "<!-- Component JavaScript Files Start -->\n";
        foreach($js_array as $url=>$usedBy) {

            echo "<script type='text/javascript' src='$url'></script>";
            echo "<!-- Used by: ".implode("; ", array_keys($usedBy))." -->\n";
        }
        echo "<!-- Component JavaScript Files End -->\n";

        echo "<!-- SparkPage JavaScript Files Start -->";
        foreach ($this->js_files as $file => $is_local) {
            $href = $file;
            if ($is_local) {
                $href = SITE_ROOT . "js/" . $file;
            }
            echo "<script type='text/javascript' src='$href'></script>";
            echo "\n";
        }
        echo "<!-- SparkPage JavaScript Files End -->";

        echo "\n";
        echo "<!-- SparkPage JavaScript End -->\n";

    }

    public function __construct(Authenticator $auth = NULL, string $loginURL = "")
    {

        parent::__construct();

        if ($auth) {

            debug(get_class($this) . " Authenticator: " . get_class($auth));

            $this->context = $auth->authorize();

            if ($this->context) {

                debug(get_class($this) . " Authorization success");

            }
            else {

                debug(get_class($this) . " Authorization failed");

                if (isset($_GET["ajax"])) {
                    throw new Exception("Your session is expired");
                }

                if (strlen($loginURL)>0) {
                    header("Location: $loginURL");
                    exit;
                }

                throw new Exception("Authorization failed");
                //redirect routines

            }

        }

        $dialog = new MessageDialog();

    }


    public function setPreferredTitle(string $page_title)
    {
        $this->preferred_title = $page_title;
    }

    public function getPreferredTitle()
    {
        return $this->preferred_title;
    }

    public function obCallback(string $buffer)
    {

        $title = $this->preferred_title . TITLE_PATH_SEPARATOR . SITE_TITLE;

        $buffer = preg_replace('#(<title.*?>).*?(</title>)#', "<title>" . strip_tags($title) . "</title>", $buffer);

        $keywords_config = "";
        $description_config = "";

        $meta_keywords = "";
        $meta_description = "";

        if ($this->keywords) {
            $meta_keywords = $this->keywords;
        }
        else {
            $meta_keywords = $this->config_keywords;
        }
        if ($this->description) {
            $meta_description = $this->description;
        }
        else {
            $meta_description = $this->config_description;
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

        try {
            if (DB_ENABLED) {
                $config = ConfigBean::factory();
                $config->setSection("seo");

                $this->config_keywords = $config->getValue("meta_keywords");
                $this->config_description = $config->getValue("meta_description");

            }
        }
        catch (Exception $e) {
            error_log("Unable to access seo config section: " . $e->getMessage() . " | URI: " . $_SERVER["REQUEST_URI"]);
            ob_start();
            var_dump($e->getTrace());
            $trace = ob_get_contents();
            ob_end_clean();
            error_log($trace);
        }

        ob_start(array($this, 'obCallback'));

        $this->htmlStart();
        $this->headStart();
        $this->headEnd();

        echo "\n<!--startRender SparkPage-->\n";

        $this->bodyStart();

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

        $this->bodyEnd();

        echo "\n<!--finishRender SparkPage-->\n";

        $this->htmlEnd();

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
