<?php
include_once("components/renderers/menus/MenuItemRenderer.php");

class MenuListRenderer extends Component {

    protected ?MenuItemList $itemList = null;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("ItemList");
        $this->setRenderEnabled(false);
        $this->itemList = null;
    }

    public function setItemList(MenuItemList $itemList) : void
    {
        if ($itemList->count()>0) {
            $this->itemList = $itemList;
            $this->setRenderEnabled(true);
        }
    }

    protected function renderImpl(): void
    {
        static $renderer = new MenuItemRenderer();

        $iterator = $this->itemList->iterator();
        while ($item = $iterator->next()) {
            if (!($item instanceof MenuItem)) continue;

            $renderer->setMenuItem($item);
            $renderer->render();
        }
    }
}