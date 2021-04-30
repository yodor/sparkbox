<?php
include_once("components/Component.php");
include_once("components/renderers/IMenuItemRenderer.php");

abstract class MenuItemRenderer extends Component implements IMenuItemRenderer
{

    protected $item = NULL;

    protected $linkTag = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->linkTag = new Component();
        $this->linkTag->setTagName("A");
        $this->linkTag->setComponentClass("MenuItemLink");

    }

    public function renderSeparator($idx_curr, $items_total)
    {
        if ($idx_curr < $items_total - 1) {
            echo "<div class='MenuSeparator' position='$idx_curr'><div></div></div>";
        }
    }

    public function setMenuItem(MenuItem $item)
    {

        $this->item = $item;

        $this->linkTag->clearAttribute("target");
        $this->linkTag->clearAttribute("tooltip");
        $this->linkTag->clearAttribute("href");
        $this->linkTag->setContents("");

        if ($item->getTooltip()) {
            $this->linkTag->setTooltipText($item->getTooltip());
        }

        if ($item->getTarget()) {
            $this->linkTag->setAttribute("target", $item->getTarget());
        }

        if ($item->getHref()) {
            $this->linkTag->setAttribute("href", $item->getHref());
        }

        $contents = $item->getTitle();

        if ($item->needTranslate()) {
            $contents = tr($item->getTitle());
        }

        if ($item->getIcon()) {
            $icon = $item->getIcon();
            $contents = "<div class='MenuIcon $icon'></div>" . $contents;
        }

        if (count($item->getSubmenu()) > 0) {
            $contents .= "<div class='handle'></div>";
        }
        $this->linkTag->setContents($contents);

    }

    public function getMenuItem()
    {
        return $this->item;
    }

}

?>