<?php
include_once("utils/MainMenu.php");

class PageSessionMenu extends MainMenu
{

    protected $context = NULL;

    protected $dataKey = "";

    public function __construct(AuthContext $context, array $main_menu)
    {
        parent::__construct();

        $this->context = $context;

        //assign initial menu
        $this->main_menu = $main_menu;

        $this->dataKey = sparkHash(SessionData::MENU . "|" . get_class($this) . SITE_TITLE);

        //check if there is already a menu in session and use it instead or put the inital menu to the session
        if ($context->getData()->contains($this->dataKey)) {
            $this->main_menu = unserialize($context->getData()->get($this->dataKey));
        }
        else {
            $context->getData()->set($this->dataKey, serialize($this->main_menu));
        }
    }

    //set selected menu items and add submenu 'arr_menu' to last selected node
    public function update($menuItems = array())
    {

        //find last active item
        $lastActive = $this->getBySelectedState();
        if ($lastActive instanceof MenuItem) {
            debug("'lastActive' MenuItem: '" . $lastActive->getTitle() . "' - URL: " . $lastActive->getHref());
        }
        else {
            debug("'lastActive' MenuItem is NULL");
        }

        $this->selectActive();

        $selectedItem = $this->getSelectedItem();
        if ($selectedItem instanceof MenuItem) {

            $selectedURL = new URL($selectedItem->getHref());

            if (count($menuItems) > 0) {

                $itemURL = new URL();
                $selectedItem->clearChildNodes();

                foreach ($menuItems as $idx => $item) {
                    if ($item instanceof MenuItem) {
                        $href = $item->getHref();

                        if (!str_starts_with($href, "/")) {
                            $item->setHref($selectedURL->getScriptPath() . "/" . $href);
                        }
                        $selectedItem->addMenuItem($item);
                    }
                }
            }
        }
        else {
            debug("No MenuItem selected as active");

            if ($lastActive instanceof MenuItem) {
                debug("Selecting 'lastActive' MenuItem as the current active");
                $this->setSelectedItem($lastActive);
            }

        }

        $this->context->getData()->set($this->dataKey, serialize($this->main_menu));

    }

}

?>
