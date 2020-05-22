<?php
include_once("components/Component.php");
include_once("components/renderers/IMenuItemRenderer.php");

abstract class MenuItemRenderer extends Component implements IMenuItemRenderer
{

    protected $item = NULL;

    public function __construct()
    {
        parent::__construct();

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

    }

    public function getMenuItem()
    {
        return $this->item;
    }

    public function renderIcon()
    {

        $icon = $this->getMenuItem()->getIcon();

        echo "<div class='MenuIcon $icon' ></div>";

    }

}

?>
