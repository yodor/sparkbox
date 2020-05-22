<?php
include_once("pages/SparkPage.php");

include_once("auth/AdminAuthenticator.php");
include_once("beans/AdminAccessBean.php");

include_once("responders/ChangePositionResponder.php");
include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");

include_once("utils/BeanKeyCondition.php");
include_once("utils/SQLSelect.php");

include_once("components/BeanFormEditor.php");
include_once("components/TableView.php");

include_once("utils/PageSessionMenu.php");
include_once("utils/Navigation.php");

include_once("auth/AuthContext.php");

class SparkAdminPage extends SparkPage
{


    /**
     *
     */
    const ACTION_ADD = "Add";
    const ACTION_BACK = "Back";
    const ACTION_EDIT = "Edit";
    const ACTION_DELETE = "Delete";

    protected $roles = array();

    protected $menu_bar = NULL;

    protected $navigation;

    public function __construct()
    {

        $this->navigation = new Navigation("AdminPageLib");

        $this->auth = new AdminAuthenticator();
        $this->loginURL = ADMIN_LOCAL . "/login.php";
        $this->authorized_access = TRUE;

        parent::__construct();

        //control gets here only if authorized
        $admin_access = new AdminAccessBean();
        $qry = $admin_access->queryField("userID", $this->getUserID());
        $qry->exec();
        while ($row = $qry->next()) {
            $this->roles[$row["role"]]=1;
        }

        $dynmenu = new PageSessionMenu($this->context, $this->initMainMenu());

        $this->menu_bar = new MenuBarComponent($dynmenu);
        $this->menu_bar->toggle_first = FALSE;
        $this->menu_bar->setName("admin_menu");
        $this->menu_bar->setClassName("admin_menu");

        $this->menu_bar->setAttribute("submenu_popup", "0");

        $this->addCSS(SPARK_LOCAL . "/css/AdminPage.css");
        $this->addCSS(SPARK_LOCAL . "/css/AdminButtons.css");
        $this->addCSS(SPARK_LOCAL . "/css/AdminMenu.css");


    }

    public function navigation() : Navigation
    {
        return $this->navigation;
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

    public function renderNavigationBar()
    {

        echo "<div class='page_caption'>";

        echo "<div class='page_actions'>";

        $back_action = $this->navigation->back();

        if ($back_action instanceof Action) {
            $back_action->setContents("");
            $back_action->setAttribute("action", SparkAdminPage::ACTION_BACK);
            $back_action->render();
        }

        if ($this->actions->count() > 0) {

            Action::RenderActions($this->actions->getActions());

        }

        echo "</div>";

        echo $this->name;

        echo "</div>";

    }

    public function haveRole($role)
    {
        return in_array($role, $this->roles) || (count($this->roles) == 0);
    }

    public function checkAccess($role, $do_redirect = TRUE)
    {
        $ret = $this->haveRole($role);
        if (!$ret && $do_redirect) {
            header("Location: " . ADMIN_LOCAL . "/access.php");
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
            Action::RenderActions($dynmenu->getSelectedPath(), TRUE);
            echo "</div>";
        }

        echo "<div class='welcome'>";

        $fullname = "";
        if ($this->context->getData()->contains(SessionData::FULLNAME)) {
            $fullname = $this->context->getData()->get(SessionData::FULLNAME);
        }
        echo "<span class='text_admin'>Welcome, $fullname</span>";
        ColorButton::RenderButton("Logout", ADMIN_LOCAL . "/logout.php");
        echo "</div>";

    }

    //local menu items created from the page
    protected $page_menu = array();

    public function setPageMenu(array $menu_items)
    {
        $this->page_menu = $menu_items;
    }

    public function startRender()
    {
        //allow processing of ajax responders first
        parent::startRender();

        $dynmenu = $this->menu_bar->getMainMenu();
        $dynmenu->update($this->page_menu);

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

        if (!$this->name) {
            $arr = $dynmenu->getSelectedPath();
            //default name of page from MenuItem
            if (count($arr) > 0) {
                $arr = array_reverse($arr);
                $item = $arr[0];
                if ($item instanceof MenuItem) {
                    $this->name = $item->getTitle();
                }
            }
        }

        $this->navigation->push($this->name);

        $this->renderNavigationBar();
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



}

?>
