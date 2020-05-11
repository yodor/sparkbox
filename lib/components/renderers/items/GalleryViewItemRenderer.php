<?php
include_once("components/Component.php");
include_once("components/ListView.php");
include_once("components/renderers/IActionRenderer.php");
include_once("components/renderers/IItemRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/renderers/ActionRenderer.php");

class GalleryViewItemRenderer extends Component implements IItemRenderer, IActionsCollection, IPhotoRenderer
{

    protected $view = NULL;

    protected $actions = array();

    protected $width = 256;
    protected $height = -1;

    protected $item = NULL;

    protected $actionRenderer = NULL;

    public function __construct(GalleryView $view)
    {
        parent::__construct();
        $this->view = $view;
        $this->actionRenderer = new ActionRenderer();

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/ActionRenderer.css";
        return $arr;
    }

    public function setItem($item)
    {
        $this->item = $item;

        if (isset($this->item["position"])) {
            $this->setAttribute("position", $this->item["position"]);
        }

        $photos = $this->view->getBean();

        $photoID = $this->item[$photos->key()];

        $this->setAttribute("itemID", $photoID);

        $refID = $this->view->getRefVal();
        $ref_key = $this->view->getRefKey();

        $this->setAttribute("ref_key", $ref_key);
        $this->setAttribute("ref_id", $refID);

    }

    public function getItem()
    {
        return $this->item;
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }

    public function addAction(Action $a)
    {
        $this->actions[$a->getTitle()] = $a;
    }

    public function getAction(string $title): Action
    {
        return $this->actions[$title];
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): ?array
    {
        return $this->actions;
    }

    protected function renderImpl()
    {

        $photoID = $this->item[$this->view->getBean()->key()];

        $item = new StorageItem();
        $item->id = $photoID;
        $item->className = get_class($this->view->getBean());

        $thumb_href = $item->hrefImage($this->width, $this->height);

        $image_href = $item->hrefFull();

        echo "<div class='header'>";

        if (isset($this->item["position"])) {
            echo "<div class='position'>";

            echo "<span>#" . $this->item["position"] . "</span>";

            echo "</div>";

        }

        echo "<div class='item_actions'>";

        $this->actionRenderer->render_title = TRUE;

        if (isset($this->actions["Edit"])) {
            $this->actionRenderer->setAction($this->actions["Edit"]);
            $this->actionRenderer->setData($this->item);
            $this->actionRenderer->render();
        }

        $this->actionRenderer->setAction(new PipeSeparatorAction());
        $this->actionRenderer->render();

        if (isset($this->actions["Delete"])) {
            $this->actionRenderer->setAction($this->actions["Delete"]);
            $this->actionRenderer->setData($this->item);
            $this->actionRenderer->render();
        }

        echo "</div>"; // item actions

        echo "</div>"; //header

        $tooltip = tr("Caption") . ": " . $this->item["caption"] . "<BR>";
        $tooltip .= tr("Upload Date") . ": " . dateFormat($this->item["date_upload"], TRUE);

        $named_link = get_class($this->view->getBean()) . "." . $photoID;

        $rel = get_class($this->view->getBean());

        echo "<a 
        class='ImagePopup image_slot' 
        style='background-image:url($thumb_href)' 
        href='$image_href' 
        name='$named_link' 
        rel='$rel' tooltip='" . attributeValue($tooltip) . "'
        caption='" . attributeValue($this->item["caption"]) . "' >";

        echo "</a>";

        echo "<div class='footer'>";

        $this->renderFooterAction("First");
        $this->renderFooterAction("Previous");
        $this->renderFooterAction("Next");
        $this->renderFooterAction("Last");

        echo "</div>";

    }

    public function renderFooterAction(string $title)
    {
        if (!isset($this->actions[$title])) return;

        $this->actionRenderer->render_title = FALSE;

        $this->actionRenderer->setAction($this->actions[$title]);
        $this->actionRenderer->setData($this->item);
        $this->actionRenderer->render();

    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }
}