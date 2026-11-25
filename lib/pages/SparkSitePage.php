<?php
include_once("pages/SparkPage.php");
include_once("components/Container.php");
include_once("components/Component.php");
include_once("components/MenuBar.php");
include_once("beans/MenuItemsBean.php");
include_once("utils/menu/MenuItemList.php");
include_once("utils/menu/BeanMenuFactory.php");
include_once("utils/menu/IMenuAddresses.php");
include_once("templates/TemplateContent.php");
include_once("components/CookiesPanel.php");
include_once("utils/script/Marketing.php");
include_once("objects/data/GTMQueue.php");

/**
 * The frame of a core site: the header (logo, name, a line under it), the main menu, the content, the footer.
 * A site subclass sets its parts (createHeader, createMenu, createFooter) and its css - the module page class of
 * its modules (main, pages, its own).
 *
 * Markup: div.frame > header.site_header > a.home > img.logo, .names > .name, .line
 *                   > nav.site_menu > .MenuBar
 *                   > main.site_main > the content
 *                   > footer.site_footer (> a.cookies when the site tracks)
 *         div.section.cookies - the consent panel (CookiesPanel), when the site tracks
 * The main menu selects the item of the requested address, else of the addresses the content names (IMenuAddresses).
 * The tracking of Settings > Marketing (Marketing) and the queued events (GTMQueue) go into the head.
 */
class SparkSitePage extends SparkPage
{
    protected MenuBar $menuBar;

    protected Container $frame;

    protected Container $main;

    public function __construct()
    {
        parent::__construct();

        $this->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/SparkSitePage.css");

        $this->frame = new Container(false);
        $this->frame->setComponentClass("frame");

        $header = $this->createHeader();
        if ($header instanceof Component) $this->frame->items()->append($header);

        $this->menuBar = new MenuBar($this->createMenu());
        $this->menuBar->setName(get_class($this));
        $nav = new Container(false);
        $nav->setTagName("nav");
        $nav->setComponentClass("site_menu");
        $nav->items()->append($this->menuBar);
        $this->frame->items()->append($nav);

        $this->main = new Container(false);
        $this->main->setTagName("main");
        $this->main->setComponentClass("site_main");
        $this->frame->items()->append($this->main);

        $tracking = Marketing::Enabled();

        $footer = $this->createFooter();
        if ($footer instanceof Container && $tracking) {
            //the consent can be changed at any time
            $link = new Component(false);
            $link->setTagName("a");
            $link->setComponentClass("cookies");
            $link->setAttribute("href", CookiesPanel::OPEN_URL);
            $link->setText(tr("Cookies"));
            $footer->items()->append($link);
        }
        if ($footer instanceof Component) $this->frame->items()->append($footer);

        $this->items()->append($this->frame);

        if ($tracking) $this->items()->append(new CookiesPanel());
    }

    protected function headInitialize() : void
    {
        parent::headInitialize();
        //the css and the js in bundles - fewer requests before the page renders
        $this->head()->enableBundles(Spark::GetBoolean(Config::ASSET_BUNDLES));
        //the tracking of the site (Settings > Marketing) - after the consent defaults of SparkGTM.js
        Marketing::AddTo($this->head());
    }

    protected function headFinalize() : void
    {
        parent::headFinalize();
        //the events of the previous request (a POST that redirected here)
        GTMQueue::Flush($this->head());
    }

    /**
     * The header: the logo (logoURL()) and the name of the site (Config::SITE_TITLE) linked to the home, the line
     * under the name (headerLine()) - null for none
     * @return Component|null
     */
    protected function createHeader() : ?Component
    {
        $local = Spark::Get(Config::LOCAL);

        $header = new Container(false);
        $header->setTagName("header");
        $header->setComponentClass("site_header");

        $home = new Container(false);
        $home->setTagName("a");
        $home->setComponentClass("home");
        $home->setAttribute("href", $local . "/");
        $home->setAttribute("title", Spark::Get(Config::SITE_TITLE));

        $logo = $this->logoURL();
        if ($logo !== "") {
            $img = new Component(false);
            $img->setTagName("img");
            $img->setComponentClass("logo");
            $img->setClosingTagRequired(false);
            $img->setAttribute("src", $logo);
            $img->setAttribute("alt", Spark::Get(Config::SITE_TITLE));
            $home->items()->append($img);
        }

        $names = new Container(false);
        $names->setComponentClass("names");
        $name = new Component(false);
        $name->setComponentClass("name");
        $name->setText(Spark::Get(Config::SITE_TITLE));
        $names->items()->append($name);
        if ($this->headerLine() !== "") {
            $line = new Component(false);
            $line->setComponentClass("line");
            $line->setText($this->headerLine());
            $names->items()->append($line);
        }
        $home->items()->append($names);

        $header->items()->append($home);
        return $header;
    }

    /**
     * The address of the logo image - "" for none
     * @return string
     */
    protected function logoURL() : string
    {
        return "";
    }

    /**
     * The line under the name of the site - "" for none
     * @return string
     */
    protected function headerLine() : string
    {
        return "";
    }

    /**
     * The main menu - default the menu of the admin (Content > Menus, MenuItemsBean)
     * @return MenuItemList
     * @throws Exception
     */
    protected function createMenu() : MenuItemList
    {
        $factory = new BeanMenuFactory(new MenuItemsBean());
        return $factory->menu();
    }

    /**
     * The footer: '© <year> <site title>' (its text; the cookies link is appended to a Container) - null for none
     * @return Component|null
     */
    protected function createFooter() : ?Component
    {
        $footer = new Container(false);
        $footer->setTagName("footer");
        $footer->setComponentClass("site_footer");
        $footer->setText("© " . date("Y") . " " . Spark::Get(Config::SITE_TITLE));
        return $footer;
    }

    public function getMenuBar() : MenuBar
    {
        return $this->menuBar;
    }

    /**
     * The content of the path goes into the main part of the frame
     * @param TemplateContent $content
     * @return void
     */
    public function setContent(TemplateContent $content) : void
    {
        $this->pageContent = $content;
        $config = $content->config();
        if ($config->title) $this->setTitle($config->title);
        $this->main->items()->append($content->component());
    }

    /**
     * The requested address, then the addresses the content names (a project: its section)
     * @return array
     */
    protected function menuAddresses() : array
    {
        $addresses = array(URL::Requested());
        if ($this->pageContent instanceof IMenuAddresses) {
            foreach ($this->pageContent->menuAddresses() as $address) $addresses[] = $address;
        }
        return $addresses;
    }

    /**
     * The selected menu item before the head is final; the title: the content's, else the site title
     * @return void
     */
    protected function applyTitleDescription() : void
    {
        $this->menuBar->getMenu()?->selectActive(...$this->menuAddresses());
        if (!$this->preferred_title) $this->preferred_title = Spark::Get(Config::SITE_TITLE);
        parent::applyTitleDescription();
    }
}
