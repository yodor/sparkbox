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

include_once("auth/AuthContext.php");
include_once("components/TextComponent.php");

class SparkTemplateAdminPage extends SparkPage implements IObserver
{

    /**
     *
     */
    const string ACTION_ADD = "Add";
    const string ACTION_BACK = "Back";
    const string ACTION_EDIT = "Edit";
    const string ACTION_DELETE = "Delete";

    protected MenuBar $menu_bar;

//    protected Navigation $navigation;

    protected Container $sidePane;

    protected Container $pageCaption;

    protected Container $page_actions;
    protected Container $page_filters;

    //
    protected string $path = "";
    protected array $selectedPath = array();

    public function __construct()
    {

        $this->auth = new AdminAuthenticator();
        $this->loginURL = Spark::Get(Config::ADMIN_LOCAL) . "2/login.php";
        $this->authorized_access = TRUE;

        parent::__construct();

        //control gets here only if authorized
//        $this->navigation = new Navigation("AdminPageLib");

        if (isset($_GET["path"])) {
            $this->path = $_GET["path"];
        }


        $this->menu_bar = new MenuBar(new MenuItemList());
        $this->menu_bar->setClassName("admin_menu");

        $this->menu_bar->setAttribute("noattach");

        MenuItemRenderer::$append_parent_href = true;
        MenuItemRenderer::$href_prefix = Spark::Get(Config::ADMIN_LOCAL) . "2/";

        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/AdminPage.css");
        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/AdminMenu.css");

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


        SparkEventManager::register(TemplateEvent::class, $this);

        $this->addParameterName("path");
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

        $buttonLogout = Button::LocationButton("Logout", new URL(Spark::Get(Config::ADMIN_LOCAL) . "/logout.php"));
        $adminHeader->items()->append($buttonLogout);

        $container->items()->append($this->menu_bar);

        return $container;
    }

//    public function navigation() : Navigation
//    {
//        return $this->navigation;
//    }

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
    protected function applyTitleDescription(): void
    {

        $dynmenu = $this->menu_bar->getMenu();

        $selected_path = $dynmenu->getSelectedPath();

        if (count($selected_path) == 0) {
            $selected_path[] = tr("Administration");
        }

        $this->preferred_title = Spark::SiteTitle($selected_path);
    }

//    protected function updateNavigation(): void
//    {
//        $dynmenu = $this->menu_bar->getMenu();
//
//        $path = "";
//        if (isset($_GET["path"])) {
//            $path = $_GET["path"];
//        }
//
//        $dynmenu->selectPath($path);
//
//
//        //page does not set a name, try and get the selected menu item and use for page name/caption
//        if (!$this->name) {
//            $selected = $dynmenu->getSelectedPath();
//            //default name of page from MenuItem
//            $itemsTotal = count($selected);
//            if ($itemsTotal > 0) {
//                $item = $selected[$itemsTotal-1];
//                if ($item instanceof MenuItem) {
//                    $this->name = $item->getName();
//
//                }
//            }
//        }
//
//        //push even if unnamed page
//        $this->navigation->push($this->name);
//    }

    /**
     * Update page actions and page caption title
     * @return void
     */
    protected function updatePageActions(): void
    {
        //$back_action = $this->navigation->back();

        //prepend the back action

        $last = end($this->selectedPath);
        if (isset($_GET["action"])) {

        }
        else {
            $last = prev($this->selectedPath);
        }

        if ($last) {
            $back_action = new Action();
            $back_action->setContents("");
            $back_action->setAction(SparkTemplateAdminPage::ACTION_BACK);
            $back_action->setTooltip("Go back");


            $back_action->getURL()->fromString(MenuItemRenderer::PathHref($last));
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

        //$this->updateNavigation();
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


    public function onEvent(SparkEvent $event): void
    {
        if (!($event instanceof TemplateEvent)) return;
        if (!$event->isEvent(TemplateEvent::MENU_CREATED)) return;
        $menu = $event->getSource();
        if ($menu instanceof MenuItemList) {
            $this->menu_bar->setMenu($menu);
        }

        $this->selectedPath = $menu->selectPath($this->path);
        Debug::ErrorLog("Location: $this->path | Matched: ", $this->selectedPath);

    }
}