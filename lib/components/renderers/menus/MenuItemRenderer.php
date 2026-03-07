<?php
include_once("components/Component.php");

class MenuItemRenderer extends Container
{

    protected ?Component $linkTag  = null;
    protected ?MenuListRenderer $submenu = null;

    public static string $href_prefix = "";
    public static bool $append_parent_href = false;

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("Item");

        $this->linkTag = new Component(false);
        $this->linkTag->setTagName("a");
        $this->linkTag->setComponentClass("Link");
        $this->linkTag->setAttribute("role", "menuitem");

        $this->items()->append($this->linkTag);

        $this->submenu = new MenuListRenderer();
        $this->submenu->setComponentClass("ItemList Submenu");
        $this->items()->append($this->submenu);

    }

    public function setMenuItem(MenuItem $item) : void
    {

        if ($item->count()<1) {
            $this->submenu->setRenderEnabled(false);
        }
        else {
            $this->submenu->setItemList($item);
        }

        $this->linkTag->removeAttribute("target");
        $this->linkTag->removeAttribute("tooltip");
        $this->linkTag->removeAttribute("href");
        $this->linkTag->setContents("");

        if ($item->getTooltip()) {
            $this->linkTag->setTooltip($item->getTooltip());
        }
        else {
            $this->linkTag->setAttribute("title", $item->getName());
        }

        if ($item->getTarget()) {
            $this->linkTag->setAttribute("target", $item->getTarget());
        }

        if ($item->getHref()) {
            $href = MenuItemRenderer::PathHref($item);
            $this->linkTag->setAttribute("href", $href);
            $this->linkTag->setAttribute("itemprop","url");
        }

        $contents = "<meta itemprop='name' content='".$item->getName()."'>";
        $contents.= $item->getName();

        if ($item->needTranslate()) {
            $contents = tr($item->getName());
        }

        $this->linkTag->setContents($contents);

        if ($item->count() > 0) {
            $this->setAttribute("have_submenu", "1");
        }
        else {
            $this->removeAttribute("have_submenu");
        }

        if ($item->isSelected()) {
            $this->setAttribute("active", "1");
        }
        else {
            $this->removeAttribute("active");
        }

    }

    public static function PathHref(MenuItem $item) : string
    {
        $result = $item->getHref();

        if (MenuItemRenderer::$append_parent_href) {
            $href = array();
            $href[] = $item->getHref();

            $current = $item;
            while ($parent = $current->getParent()) {
                $href[] = $parent->getHref();
                $current = $parent;
            }

            $result = implode("/", array_reverse($href));
        }

        if (MenuItemRenderer::$href_prefix) {
            $result = Spark::PathParts(MenuItemRenderer::$href_prefix , $result);
        }
        return $result;
    }
}