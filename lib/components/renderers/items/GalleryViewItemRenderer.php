<?php
include_once("lib/components/Component.php");
include_once("lib/components/ListView.php");
include_once("lib/components/renderers/IActionsRenderer.php");
include_once("lib/components/renderers/IItemRenderer.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/components/renderers/ActionRenderer.php");

class GalleryViewItemRenderer extends Component implements IItemRenderer, IActionsRenderer, IPhotoRenderer
{

    protected $gallery_view;

    protected $actions;

    protected $width = 128;
    protected $height = -1;

    protected $item = NULL;

    public function setItem($item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setPhotoSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth()
    {
        return $this->width;
    }

    public function getPhotoHeight()
    {
        return $this->height;
    }

    public function addAction(Action $a)
    {
        $this->actions[$a->getTitle()] = $a;
    }

    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function __construct(GalleryView $view)
    {
        parent::__construct();
        $this->gallery_view = $view;

    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/ActionRenderer.css";
        $arr[] = SITE_ROOT . "lib/css/GalleryView.css";
        return $arr;

    }

    public function startRender()
    {

        if (isset($this->item["position"])) {
            $this->setAttribute("position", $this->item["position"]);
        }

        $photos_bean = $this->gallery_view->getBean();
        $photos_prkey = $photos_bean->key();
        $photoID = $this->item[$photos_prkey];
        $this->setAttribute("itemID", $photoID);

        $refID = $this->gallery_view->getRefVal();
        $ref_key = $this->gallery_view->getRefKey();

        if ($ref_key) {
            $this->setAttribute("ref_key", "$ref_key");
            $this->setAttribute("ref_id", "$refID");
        }

        $all_attr = $this->prepareAttributes();
        echo "<div $all_attr >";

    }

    public function finishRender()
    {
        echo "</div>";
    }

    public function renderImpl()
    {


        $row = $this->item;


        $refID = $this->gallery_view->getRefVal();
        $ref_key = $this->gallery_view->getRefKey();

        $photos_bean = $this->gallery_view->getBean();
        $photos_prkey = $photos_bean->key();


        $photoID = $row[$photos_prkey];

        $item = new StorageItem();
        $item->id = $photoID;
        $item->className = get_class($photos_bean);

        $thumb_href = $item->hrefImage($this->width, $this->height);

        $image_href = $item->hrefFull();

        echo "<div class='header_row'>";


        if (isset($row["position"])) {
            echo "<div class='item_position'>";

            echo "<label>" . tr("Position") . ": </label><span>" . $row["position"] . "</span>";

            echo "</div>";

        }

        $this->renderActions($row);

        echo "<div class=clear></div>";

        echo "</div>";


        $tooltip = tr("Caption") . ": " . $row["caption"] . "<BR>";
        $tooltip .= tr("Upload Date") . ": " . dateFormat($row["date_upload"], true);


        echo "<div class='image_slot'>";

        // 		$named_link = get_class($this->gallery_view->getBean()).".".$this->item["position"];
        $named_link = get_class($this->gallery_view->getBean()) . "." . $photoID;

        $rel = get_class($this->gallery_view->getBean());

        echo "<a class='image_popup' href='$image_href' name='$named_link' rel='" . get_class($this->gallery_view->getBean()) . "'>";
        echo "<img src='$thumb_href' tooltip='" . attributeValue($tooltip) . "'>";
        echo "</a>";

        if ($this->actions["First"]) {

            $this->renderRepositionAction($this->actions["First"], $row);

        }
        if ($this->actions["Last"]) {

            $this->renderRepositionAction($this->actions["Last"], $row);

        }
        if ($this->actions["Previous"]) {

            $this->renderRepositionAction($this->actions["Previous"], $row);

        }
        if ($this->actions["Next"]) {

            $this->renderRepositionAction($this->actions["Next"], $row);

        }

        echo "</div>";


    }

    public function renderRepositionAction($act, &$row)
    {
        $act = new ActionRenderer($act, $row);
        $act->setClassName("reposition_action");
        $act->render_title = false;
        $act->render();


    }

    public function renderActions(array &$row)
    {

        echo "<div class='item_actions'>";

        if (isset($this->actions["Edit"])) {

            $act = new ActionRenderer($this->actions["Edit"], $row);
            $act->render();


        }

        $act = new ActionRenderer(new PipeSeparatorAction(), NULL);
        $act->render();

        if (isset($this->actions["Delete"])) {
            $act = new ActionRenderer($this->actions["Delete"], $row);
            $act->render();


        }

        echo "</div>";


    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }
}