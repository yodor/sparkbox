<?php
include_once("pages/SparkPage.php");

include_once("auth/AdminAuthenticator.php");
include_once("beans/AdminAccessBean.php");

include_once("responders/ChangePositionResponder.php");
include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");

include_once("utils/BeanKeyCondition.php");
include_once("sql/SQLSelect.php");

include_once("components/BeanFormEditor.php");
include_once("components/TableView.php");
include_once("components/ClosureComponent.php");

include_once("utils/menu/PageSessionMenu.php");
include_once("utils/Navigation.php");

include_once("auth/AuthContext.php");
include_once("components/TextComponent.php");

class SparkAdminPage extends SparkPage
{

    /**
     *
     */
    const string ACTION_ADD = "Add";
    const string ACTION_BACK = "Back";
    const string ACTION_EDIT = "Edit";
    const string ACTION_DELETE = "Delete";

    protected array $roles = array();

    protected MenuBarComponent $menu_bar;

    protected Navigation $navigation;

    protected Container $page_filters;
    protected Container $page_caption;

    public function __construct()
    {

        $this->navigation = new Navigation("AdminPageLib");

        $this->auth = new AdminAuthenticator();
        $this->loginURL = ADMIN_LOCAL . "/login.php";
        $this->authorized_access = TRUE;

        parent::__construct();

        $this->authorize();

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

        $this->menu_bar->getBar()->setAttribute("submenu_popup", "0");

        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminPage.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminButtons.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminMenu.css");


        $this->page_caption = new Container(false);
        $this->page_caption->setClassName("page_caption");

        $this->page_filters = new Container(false);
        $this->page_filters->setClassName("page_filters");



    }

    public function navigation() : Navigation
    {
        return $this->navigation;
    }

    protected function initMainMenu() : array
    {

        $admin_menu = array();
        $admin_menu[] = new MenuItem("Content", ADMIN_LOCAL . "/content/index.php", "content");
        $admin_menu[] = new MenuItem("Settings", ADMIN_LOCAL . "/settings/index.php", "settings");

        return $admin_menu;
    }

    public function renderNavigationBar()
    {

        $page_actions = new Container(false);
        $page_actions->setClassName("page_actions");

        $back_action = $this->navigation->back();

        if ($back_action instanceof Action) {
            $back_action->setContents("");
            $back_action->setAttribute("action", SparkAdminPage::ACTION_BACK);
            $back_action->setTooltipText("Go back");
            $page_actions->items()->append($back_action);
        }

        foreach($this->actions->toArray() as $action) {
            $page_actions->items()->append($action);
        }

        $title = new TextComponent($this->name);
        $title->setClassName("page_title");
        $page_actions->items()->append($title);

        $this->page_caption->items()->append($page_actions);

        $this->page_caption->items()->append($this->page_filters);

        $this->page_caption->render();
    }

    public function getPageCaption() : Container
    {
        return $this->page_caption;
    }

    public function getPageFilters() : Container
    {
        return $this->page_filters;
    }

    public function haveRole($role) : bool
    {
        return in_array($role, $this->roles) || (count($this->roles) == 0);
    }

    public function checkAccess($role, $do_redirect = TRUE) : bool
    {
        $ret = $this->haveRole($role);
        if (!$ret && $do_redirect) {
            header("Location: " . ADMIN_LOCAL . "/access.php");
            exit;
        }
        return $ret;
    }


    //local menu items created from the page
    protected array $page_menu = array();

    public function setPageMenu(array $menu_items) : void
    {
        $this->page_menu = $menu_items;

    }

    protected function constructTitle(): void
    {

        $dynmenu = $this->menu_bar->getMenu();
        $dynmenu->update($this->page_menu);

        $selected_path = $dynmenu->getSelectedPath();

        if (count($selected_path) == 0) {
            //this page caption
            if ($this->name) {
                $selected_path[] = $this->name;
            }
            else {
                $selected_path[] = tr("Administration");
            }
        }


        $this->preferred_title = constructSiteTitle($selected_path);

    }

    public function startRender()
    {

        //allow processing of ajax responders first
        parent::startRender();

        echo "\n<!-- startRender SparkAdminPage -->\n";

        echo "<div class='admin_layout'>";

//        echo "<tr>";

            echo "<div class='left_menu'>";

                echo "<div class='menu_contents'>";

                    echo "<div class='admin_header'>";

                    $fullname = "";
                    if ($this->context->getData()->contains(SessionData::FULLNAME)) {
                        $fullname = $this->context->getData()->get(SessionData::FULLNAME);
                    }
                    echo "<div class='username'>".$fullname."</div>";

                    ColorButton::RenderButton("Logout", ADMIN_LOCAL . "/logout.php");

                    echo "</div>";

                    $this->menu_bar->render();

                echo "</div>";

            echo "</div>";

            echo "<div class='page_area' >";

            $dynmenu = $this->menu_bar->getMenu();

            if (!$this->name) {
                $arr = $dynmenu->getSelectedPath();
                //default name of page from MenuItem
                if (count($arr) > 0) {
                    $arr = array_reverse($arr);
                    $item = $arr[0];
                    if ($item instanceof MenuItem) {
                        $this->name = $item->getName();
                    }
                }
            }

            $this->navigation->push($this->name);

            $this->renderNavigationBar();

            echo "<div class='page_contents'>";
    }

    public function finishRender()
    {
            echo "</div>"; //page_contents

            echo "</div>";//page_area


        echo "</div>";//admin_layout

        echo "\n<!-- finishRender SparkAdminPage -->\n";

        parent::finishRender();
    }



}

?>
