<?php
include_once("pages/SparkPage.php");

include_once("auth/AdminAuthenticator.php");
include_once("beans/AdminAccessBean.php");

include_once("handlers/ChangePositionRequestHandler.php");
include_once("handlers/DeleteItemRequestHandler.php");
include_once("handlers/ToggleFieldRequestHandler.php");

include_once("utils/RequestBeanKey.php");
include_once("utils/SQLSelect.php");

include_once("components/BeanFormEditor.php");
include_once("components/TableView.php");

include_once("utils/PageSessionMenu.php");

include_once("auth/AuthContext.php");

class AdminPageLib extends SparkPage
{

    public $caption = "";

    protected $roles = array();

    protected $menu_bar = NULL;

    public function __construct()
    {

        $this->auth = new AdminAuthenticator();
        $this->loginURL = LOCAL."admin/login.php";
        $this->authorized_access = true;

        parent::__construct();

        //control gets here only if authorized
        $adminID = $this->getUserID();

        $b = new AdminAccessBean();
        $qry = $b->queryField("userID", $adminID);
        $n = $qry->exec();

        while ($row = $qry->next()) {
            $this->roles[] = $row["role"];
        }

        $dynmenu = new PageSessionMenu($this->context, $this->initMainMenu());

        $this->menu_bar = new MenuBarComponent($dynmenu);
        $this->menu_bar->toggle_first = false;
        $this->menu_bar->setName("admin_menu");
        $this->menu_bar->setClassName("admin_menu");

        $this->menu_bar->setAttribute("submenu_popup", "0");


        $this->addCSS(SPARK_LOCAL . "/css/admin.css", false);
        $this->addCSS(SPARK_LOCAL . "/css/admin_buttons.css", false);
        $this->addCSS(SPARK_LOCAL . "/css/admin_menu.css", false);
        $this->addCSS(SPARK_LOCAL . "/css/admin.css", false);

    }

    protected function initMainMenu()
    {
        global $admin_menu;

        if (!isset($admin_menu)) {

            $admin_menu = array();
            $admin_menu[] = new MenuItem("Content", ADMIN_LOCAL . "/content/index.php", "class:icon_content");
            $admin_menu[] = new MenuItem("Settings", ADMIN_LOCAL . "/settings/index.php", "class:icon_settings");

        }

        return $admin_menu;
    }

    public function renderPageCaption($str = NULL)
    {

        $caption = "";


        $dynmenu = $this->menu_bar->getMainMenu();
        $arr = $dynmenu->getSelectedPath();


        //default caption of page from MenuItem
        if (count($arr) > 0) {
            $arr = array_reverse($arr);
            $item = $arr[0];
            if ($item instanceof MenuItem) {
                $caption = $item->getTitle();
            }
        }


        //property caption
        if ($this->caption) {
            $caption = $this->caption;
        }
        else if ($str) {
            $caption = $str;
        }


        if ($caption) {
            echo "<div class='page_caption'>";


            if (count($this->actions) > 0) {
                $renderer = new ActionRenderer();
                echo "<div class='page_actions'>";
                $renderer->renderActions($this->actions);
                echo "</div>";
            }
            echo $caption;
            echo "</div>";
        }
    }


    public function haveRole($role)
    {
        return in_array($role, $this->roles) || (count($this->roles) == 0);
    }

    public function checkAccess($role, $do_redirect = true)
    {
        $ret = $this->haveRole($role);
        if (!$ret && $do_redirect) {
            header("Location: " . ADMIN_LOCAL . "access.php");
            exit;
        }
        return $ret;
    }

    public function renderAdminHeader()
    {
        $dynmenu = $this->menu_bar->getMainMenu();

        $arr = $dynmenu->getSelectedPath();
        if (count($arr) > 0) {
            echo "<div class='LocationPath'>";
            echo "<label>" . tr("Location") . ": </label>";
            $act = new ActionRenderer();
            $act->enableSeparator(true);
            $act->renderActions($dynmenu->getSelectedPath());
            echo "</div>";
        }

        echo "<div class='welcome'>";

        $fullname = "";
        if ($this->context->getData()->contains(SessionData::FULLNAME)) {
            $fullname = $this->context->getData()->get(SessionData::FULLNAME);
        }
        echo "<span class='text_admin'>Welcome, $fullname</span>";
        ColorButton::RenderButton("Logout", ADMIN_LOCAL . "logout.php");
        echo "</div>";


    }

    public function startRender($arr_menu = array())
    {
        //allow processing of ajax handlers first
        parent::startRender();

        $dynmenu = $this->menu_bar->getMainMenu();

        $dynmenu->update($arr_menu);

        $this->preferred_title = constructSiteTitle($dynmenu->getSelectedPath());

        echo "\n<!-- startRender AdminPageLib -->\n";

        echo "<table class='admin_layout'>";

        echo "<tr>";

        echo "<td class='admin_header' colspan=2>";

        $this->renderAdminHeader();

        echo "</td>";

        echo "</tr>";

        echo "<tr>";

        echo "<td class='left_menu'>";


        $this->menu_bar->render();


        if (is_callable("drawMenuPrivate")) {
            call_user_func("drawMenuPrivate", $this);
        }

        echo "</td>";

        echo "<td class='page_area " . $this->getPageClass() . "'>";
        echo "\n\n";

        $this->renderPageCaption();
    }

    public function finishRender()
    {

        echo "</td>";//page_area
        echo "</tr>";

        echo "<tr><td colspan=2 class='admin_footer'>";

        echo "<span class='copy'>Copyright &copy; " . date("Y") . " " . SITE_TITLE . ". All Rights Reserved.</span>";
        echo "<img class='logo' src='" . SPARK_LOCAL . "/images/admin/sparkbox.png'>";

        echo "</td></tr>";
        echo "</table>";

        echo "\n<!-- finishRender AdminPageLib -->\n";

        parent::finishRender();
    }

    const ACTION_ADD = "add";
    const ACTION_BACK = "back";

    public static function CreateAction(string $action, string $title = "", array $parameters = array()) : Action
    {
        $action_add = new Action("", "$action.php", $parameters);
        $action_add->setAttribute("action", $action);
        if (!$title) $title = ucfirst($action);
        $action_add->setAttribute("title", $title);
        return $action_add;
    }

}

?>
