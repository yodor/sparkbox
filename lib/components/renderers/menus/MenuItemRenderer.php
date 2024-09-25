<?php
include_once("components/Component.php");
include_once("components/renderers/IMenuItemRenderer.php");

abstract class MenuItemRenderer extends Component implements IMenuItemRenderer
{

    protected ?MenuItem $item = null;

    protected ?Component $linkTag  = null;

    public function __construct()
    {
        parent::__construct(false);
        $this->linkTag = new Component(false);
        $this->linkTag->setTagName("A");
        $this->linkTag->setComponentClass("MenuItemLink");
        $this->linkTag->setAttribute("role", "menuitem");
    }

    public function renderSeparator(int $idx_curr, int $items_total) : void
    {
        if ($idx_curr < $items_total - 1) {
            echo "<div class='MenuSeparator' position='$idx_curr'><div></div></div>";
        }
    }

    public function setMenuItem(MenuItem $item) : void
    {

        $this->item = $item;

        $this->linkTag->removeAttribute("target");
        $this->linkTag->removeAttribute("tooltip");
        $this->linkTag->removeAttribute("href");
        $this->linkTag->setContents("");

        if ($item->getTooltip()) {
            $this->linkTag->setTooltipText($item->getTooltip());
        }

        if ($item->getTarget()) {
            $this->linkTag->setAttribute("target", $item->getTarget());
        }

        if ($item->getHref()) {
            $this->linkTag->setAttribute("href", $item->getHref());
            $this->linkTag->setAttribute("itemprop","url");
        }

        $contents = "<meta itemprop='name' content='".$item->getName()."'>";
        $contents.= $item->getName();

        if ($item->needTranslate()) {
            $contents = tr($item->getName());
        }

        if ($item->getIcon()) {
            $icon = $item->getIcon();
            $contents = "<div class='MenuIcon $icon'></div>" . $contents;
        }

        if ($item->count() > 0) {
            $contents .= "<div class='handle'></div>";
        }
        $this->linkTag->setContents($contents);

        $this->setAttribute("title", $item->getName());

    }

    public function getMenuItem() : ?MenuItem
    {
        return $this->item;
    }

}

?>
