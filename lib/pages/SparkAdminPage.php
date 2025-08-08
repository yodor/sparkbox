<?php
include_once("pages/SparkPage.php");

include_once("auth/AdminAuthenticator.php");
include_once("beans/AdminAccessBean.php");

include_once("responders/ChangePositionResponder.php");
include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");
include_once("dialogs/json/JSONFormDialog.php");

include_once("utils/BeanKeyCondition.php");
include_once("sql/SQLSelect.php");

include_once("components/MenuBar.php");
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

    protected MenuBar $menu_bar;

    protected Navigation $navigation;

    protected Container $sidePane;

    protected Container $pageCaption;

    protected Container $page_actions;
    protected Container $page_filters;


    //local menu items created from the page
    protected array $page_menu = array();

    public function setPageMenu(array $menu_items) : void
    {
        $this->page_menu = $menu_items;

    }

    public function __construct()
    {

        $this->auth = new AdminAuthenticator();
        $this->loginURL = ADMIN_LOCAL . "/login.php";
        $this->authorized_access = TRUE;

        parent::__construct();

        $this->authorize();

        //control gets here only if authorized
        $this->navigation = new Navigation("AdminPageLib");

        $menuItems = $this->initMainMenu();

        $access_level = $this->context->getData()->get(AdminAuthenticator::SESSION_DATA_ACCESS_LEVEL);
        if (strcmp($access_level, AdminAuthenticator::ACCESS_LIMITED) == 0) {
            $roles = $this->context->getData()->get(AdminAuthenticator::SESSION_DATA_ENABLED_ROLES);

            $enabledMenu = array();

            for ($i=0; $i<count($menuItems); $i++) {
                $item = $menuItems[$i];
                if ($item instanceof MenuItem) {
                    if (in_array($item->getName(), $roles)) {
                        $enabledMenu[] = $item;
                    }
                }
            }

            $menuItems = $enabledMenu;

            $currentBasePath = pathInfo(URL::Current()->toString(), PATHINFO_DIRNAME);
            if (strcmp($currentBasePath, ADMIN_LOCAL ) != 0) {

                $found = false;
                foreach ($enabledMenu as $menuItem) {
                    if ($menuItem instanceof MenuItem) {
                        $menuBasePath = pathinfo($menuItem->getHref(), PATHINFO_DIRNAME);

                        if (str_contains($currentBasePath, $menuBasePath)) {
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
//                    throw new Exception("Access denied");
                    header("Location: ".ADMIN_LOCAL."/access.php");
                    exit;
                }
            }

        }




        $dynmenu = new PageSessionMenu($this->context, $menuItems);
        $dynmenu->setName("admin_menu");

        $this->menu_bar = new MenuBar($dynmenu);
        $this->menu_bar->setClassName("admin_menu");

        $this->menu_bar->setAttribute("noattach");

        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminPage.css");
        $this->head()->addCSS(SPARK_LOCAL . "/css/AdminMenu.css");

        $this->body()->addClassName("admin_layout");

        $this->sidePane = $this->createSidePane();


        $this->pageCaption = new Container(false);
        $this->pageCaption->setComponentClass("page_caption");

        $this->page_actions = new Container(false);
        $this->page_actions->setComponentClass("page_actions");
        $this->pageCaption->items()->append($this->page_actions);

        $this->page_filters = new Container(false);
        $this->page_filters->setComponentClass("page_filters");
        $this->pageCaption->items()->append($this->page_filters);

        $this->setTitle(tr("Administration"));

        $dialog = new JSONFormDialog();
    }

    protected function createSidePane() : Container
    {
        $container = new Container(false);
        $container->setComponentClass("sidePane");

        $adminHeader = new Container(false);
        $adminHeader->setComponentClass("admin_header");
        $container->items()->append($adminHeader);

        $username = $this->context->getData()->get(SessionData::FULLNAME);
        if ($username) {
            $adminName = new TextComponent($username);
            $adminName->setComponentClass("username");
            $adminHeader->items()->append($adminName);
        }

        $buttonLogout = Button::LocationButton("Logout", new URL(ADMIN_LOCAL . "/logout.php"));
        $adminHeader->items()->append($buttonLogout);

        $container->items()->append($this->menu_bar);

        return $container;
    }

    public function navigation() : Navigation
    {
        return $this->navigation;
    }

    protected function initMainMenu() : array
    {
        return array();
    }

    public function getPageCaption() : Container
    {
        return $this->pageCaption;
    }

    public function getPageActions() : Container
    {
        return $this->page_actions;
    }

    public function getPageFilters() : Container
    {
        return $this->page_filters;
    }

    /**
     * After request controllers before start render
     * @return void
     * @throws Exception
     */
    protected function constructTitle(): void
    {

        $dynmenu = $this->menu_bar->getMenu();

        $selected_path = $dynmenu->getSelectedPath();

        if (count($selected_path) == 0) {
            $selected_path[] = tr("Administration");
        }

        $this->preferred_title = constructSiteTitle($selected_path);
    }

    protected function updateNavigation(): void
    {
        $dynmenu = $this->menu_bar->getMenu();

        //update here
        $dynmenu->update($this->page_menu);

        //page does not set a name, try and get the selected menu item and use for page name/caption
        if (!$this->name) {
            $selected = $dynmenu->getSelectedPath();
            //default name of page from MenuItem
            $itemsTotal = count($selected);
            if ($itemsTotal > 0) {
                $item = $selected[$itemsTotal-1];
                if ($item instanceof MenuItem) {
                    $this->name = $item->getName();

                }
            }
        }

        //push even if unnamed page
        $this->navigation->push($this->name);
    }

    /**
     * Update page actions and page caption title
     * @return void
     */
    protected function updatePageActions(): void
    {
        $back_action = $this->navigation->back();

        //prepend the back action
        if ($back_action instanceof Action) {
            $back_action->setContents("");
            $back_action->setAttribute("action", SparkAdminPage::ACTION_BACK);
            $back_action->setTooltip("Go back");
            $this->page_actions->items()->append($back_action);
        }

        //fill all actions into the page actions container
        foreach ($this->actions->toArray() as $action) {
            $this->page_actions->items()->append($action);
        }

        //set the page title
        $title = new TextComponent($this->name);
        $title->setClassName("page_title");
        $this->page_actions->items()->append($title);
    }

    public function startRender(): void
    {

        $this->updateNavigation();
        $this->updatePageActions();

        //allow processing of responders, constructTitle and prepareMetaTitle
        parent::startRender();

        echo "\n<!-- startRender SparkAdminPage -->\n";

        $this->sidePane->render();

        echo "<div class='page_area' >";

        $this->pageCaption->render();

        echo "<div class='page_contents'>";
    }

    public function finishRender(): void
    {
        echo "</div>"; //page_contents

        echo "</div>";//page_area

        echo "\n<!-- finishRender SparkAdminPage -->\n";

        parent::finishRender();
    }

    public function getMenuBar() : MenuBar
    {
        return $this->menu_bar;
    }

}

?>
