<?php
include_once("pages/SparkTemplatePage.php");


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
include_once("utils/Navigation.php");


include_once("responders/json/TemplateHelpResponder.php");


class SparkTemplateAdminPage extends SparkTemplatePage
{

    /**
     *
     */
    const string ACTION_ADD = "Add";
    const string ACTION_BACK = "Back";
    const string ACTION_EDIT = "Edit";
    const string ACTION_DELETE = "Delete";
    const string ACTION_HELP = "Help";

    protected MenuBar $menu_bar;

    protected Navigation $navigation;

    protected Container $filters;

    protected Container $base;

    protected static ?MenuItemList $Menu = null;

    public function __construct()
    {

        $this->context = ModuleConfig::Active()->authContext;

        parent::__construct();

        //control gets here only if authorized
        $this->navigation = new Navigation("SparkAdmin");

        $this->menu_bar = new MenuBar(new MenuItemList());
        $this->menu_bar->setClassName("admin_menu");
        $this->menu_bar->setAttribute("noattach");

        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/SparkTemplateAdminPage.css");
        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/AdminMenu.css");

        $this->head()->addJS(Spark::Get(Config::SPARK_LOCAL) . "/js/TemplateHelp.js");

        $this->body()->addClassName("admin_layout");

        $headerPane = new Container(false);
        $headerPane->setComponentClass("headerPane");
        $this->items()->append($headerPane);

        $basePane = new Container(false);
        $basePane->setComponentClass("basePane");
        $this->items()->append($basePane);

            $sidePane = $this->createSidePane();
            $sidePane->setComponentClass("sidePane");
            $basePane->items()->append($sidePane);

            $mainPane = $this->createMainPane();
            $mainPane->setComponentClass("mainPane");
            $basePane->items()->append($mainPane);

//            $helpPane = $this->createHelpPane();
//            $helpPane->setComponentClass("helpPane");
//            $basePane->items()->append($helpPane);

        $footerPane = new Container(false);
        $footerPane->setComponentClass("footerPane");
        $this->items()->append($footerPane);

        $this->setTitle(tr("Administration"));

        $dialog = new JSONFormDialog();

        $helpFetcher = new TemplateHelpResponder();


        //initialize menu and module
        MenuItemRenderer::$append_parent_href = true;
        MenuItemRenderer::$href_prefix = Spark::Get(Config::ADMIN_LOCAL) . "2/";

        Template::SetModule("admin", MenuItemRenderer::$href_prefix);

        SparkTemplateAdminPage::$Menu = null;
        SparkLoader::Factory(Template::ModuleLocation())->include("menu", true);

        if (SparkTemplateAdminPage::$Menu instanceof MenuItemList) {

            //register late after all inclusions are to allow their event registration if there is
            SparkEventManager::register(TemplateMenuEvent::class, $this);

            //fire TemplateEvent::MENU_CREATED
            SparkEventManager::emit(new TemplateMenuEvent(TemplateMenuEvent::CREATED, SparkTemplateAdminPage::$Menu));
        }

        //TODO: check menu access
    }



    public function initialize() : void
    {
        parent::initialize();
    }

    protected function createSidePane() : Container
    {
        $sidePane = new Container(false);
        $sidePane->setComponentClass("sidePane");

        $adminData = new Container(false);
        $adminData->setComponentClass("user_data");
        $sidePane->items()->append($adminData);

        $username = $this->context->getData()->get(SessionData::FULLNAME);
        if ($username) {
            $adminName = new TextComponent($username);
            $adminName->setComponentClass("username");
            $adminData->items()->append($adminName);
        }

        $buttonLogout = Button::LocationButton("Logout", new URL(Spark::Get(Config::ADMIN_LOCAL) . "/logout.php"));
        $adminData->items()->append($buttonLogout);

        $sidePane->items()->append($this->menu_bar);

        return $sidePane;
    }

    protected function createMainPane() : Container
    {
        $mainPane = new Container(false);
        $mainPane->setComponentClass("mainPane");

        $header = new Container(false);
        $header->setComponentClass("header");
        $mainPane->items()->append($header);

            $caption = new Container(false);
            $caption->setComponentClass("actions_title");
            $header->items()->append($caption);

                $actions = new Container(false);
                $actions->setComponentClass("actions");
                $caption->items()->append($actions);

                $title = new Container(false);
                $title->setComponentClass("title");
                $caption->items()->append($title);

            $this->filters = new Container(false);
            $this->filters->setComponentClass("filters");
            $header->items()->append($this->filters);

        $this->base = new Container(false);
        $this->base->setComponentClass("base");
        $mainPane->items()->append($this->base);

        $helpPane = $this->createHelpPane();
        $helpPane->setComponentClass("helpPane");
        $this->base->items()->append($helpPane);

        $footer = new Container(false);
        $footer->setComponentClass("footer");
        $mainPane->items()->append($footer);

        return $mainPane;
    }
    protected function createHelpPane() : Container
    {
        $helpPane = new Container(false);
        $helpPane->setComponentClass("helpPane");
        return $helpPane;
    }

    public function navigation() : Navigation
    {
        return $this->navigation;
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

    protected function updateNavigation(): void
    {
        $selectedPath = $this->menu_bar->getMenu()->selectPath($this->path);
        Debug::ErrorLog("Location: $this->path | Matched: ", $selectedPath);

        $name = $this->getName();

        if (!$name) {
            end($selectedPath);
            $item = current($selectedPath);
            //default name of page from MenuItem
            if ($item instanceof MenuItem) {
                $name = $item->getName();
            }
        }
        if (!$name) {
            $name = "Unnamed";
        }

        $this->setName($name);

        //push even if unnamed page?
        $this->navigation->push($this->getName());


    }

    /**
     * Update page actions and page caption title
     * @return void
     */
    protected function setPageActions(): void
    {

        $actions_title = $this->items()
            ->getByContainerClass("basePane")->items()
            ->getByContainerClass("mainPane")->items()
            ->getByContainerClass("header")->items()
            ->getByContainerClass("actions_title");

        $actions = $actions_title->items()
            ->getByContainerClass("actions");

        $this->navigation->end();
        $this->navigation->prev();
        $url = $this->navigation->current();
        if ($url instanceof URL) {
            $back_action = new Action();
            $back_action->setAction(SparkTemplateAdminPage::ACTION_BACK);
            $back_action->setURL(Template::PathURL("", $url));
            $actions->items()->append($back_action);
        }

        //fill all actions into the page actions container
        foreach ($this->actions->toArray() as $action) {
            $actions->items()->append($action);
        }

        $help_action = new Action();
        $help_action->setAction(SparkTemplateAdminPage::ACTION_HELP);
        $help_action->setAttribute("path", $this->path);
        $help_action->setAttribute("sink", "helpPane");
        $help_action->setAttribute("onClick", "javascript:document.templateHelp.fetch(this)");
        $actions_title->items()->append($help_action);
    }

    protected function setPageTitle() : void
    {
        $title = $this->items()
            ->getByContainerClass("basePane")->items()
            ->getByContainerClass("mainPane")->items()
            ->getByContainerClass("header")->items()
            ->getByContainerClass("actions_title")->items()
            ->getByContainerClass("title");

        $title->setContents($this->getName());
    }

    public function update(TemplateContent $content) : void
    {
        parent::update($content);

        $this->setName($content->config()->title);

        if ($content->config()->description) {
            $content->component()->setCaption($content->config()->description);
        }

        $this->base->items()->append($content->component());
        foreach( Spark::ClassChain($content) as $pos=>$name) {
            $this->base->addClassName($name);
        }

        $content->fillPageActions($this->getActions());
        $content->fillPageFilters($this->filters);

        if ($content->config()->clearNavigation) {
            $this->navigation->clear();
        }

        $this->updateNavigation();

        $this->setPageTitle();
        $this->setPageActions();
    }

    public function onEvent(SparkEvent $event): void
    {
        //handle Template::LoadContent
        parent::onEvent($event);

        if ($event->isEvent(TemplateMenuEvent::CREATED)) {

            //Debug::ErrorLog("MenuItemList top level count: ". SparkTemplateAdminPage::$Menu->count());
            foreach (Template::DisabledPaths() as $idx=>$disabledPath) {
                SparkTemplateAdminPage::$Menu->removePath($disabledPath);
            }

            $this->menu_bar->setMenu(SparkTemplateAdminPage::$Menu);

        }

    }

    public function getMenuBar() : MenuBar
    {
        return $this->menu_bar;
    }

    public static function SetMenu(MenuItemList $menu) : void
    {
        //TODO: unregister listeners ?
        SparkTemplateAdminPage::$Menu = $menu;

    }
}