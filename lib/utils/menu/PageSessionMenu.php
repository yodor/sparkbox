<?php
include_once("utils/menu/MenuItemList.php");

class PageSessionMenu extends MenuItemList
{

    protected AuthContext $context;

    protected string $dataKey = "";

    public function __construct(AuthContext $context, array $main_menu)
    {
        parent::__construct($main_menu);

        $this->context = $context;
        $this->dataKey = get_class($this);

        //check if there is already a menu in session and use it instead or put the initial menu to the session
        if ($context->getData()->contains($this->dataKey)) {
            $this->elements = $context->getData()->get($this->dataKey);
        }
        else {
            $context->getData()->set($this->dataKey, $this->elements);
        }
    }

    /**
     * Find active and clear append with $menuItems
     * @param array $menuItems
     * @return void
     */
    public function update(array $menuItems = array()) : void
    {

        //find the MenuItem that was lastly selected
        $lastActive = $this->getSelected();

        if ($lastActive instanceof MenuItem) {
            debug("'lastActive' MenuItem: '" . $lastActive->getName() . "' - URL: " . $lastActive->getHref());
        }
        else {
            debug("'lastActive' MenuItem is NULL");
        }

        $selectedItem = $this->selectActive();

        if ($selectedItem instanceof MenuItem) {
            //clear and append selected with $menuItems
            if (count($menuItems) > 0) {

                $selectedURL = new URL($selectedItem->getHref());
                $selectedItem->clear();

                foreach ($menuItems as $idx => $item) {

                    if (! ($item instanceof MenuItem)) continue;

                    $href = $item->getHref();
                    //TODO: check usage
                    if (!str_starts_with($href, "/")) {
                        $item->setHref($selectedURL->getScriptPath() . "/" . $href);
                    }
                    $selectedItem->append($item);

                }
            }
        }
        else {
            debug("No MenuItem selected as active");

            if ($lastActive instanceof MenuItem) {
                debug("Selecting 'lastActive' MenuItem as the current active");
                $lastActive->setSelected(true);
            }

        }


        //store
        $this->context->getData()->set($this->dataKey, $this->elements);

    }

}

?>
