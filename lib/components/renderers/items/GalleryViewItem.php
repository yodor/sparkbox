<?php
include_once("components/renderers/items/DataIteratorItem.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/ItemView.php");
include_once("components/Action.php");

class GalleryViewItem extends DataIteratorItem implements IActionCollection, IPhotoRenderer
{

    protected $actions;

    /**
     * @var GalleryView
     */
    protected $view;

    /**
     * @var Action
     */
    protected $action;

    /**
     * @var ImagePopup
     */
    protected $image_popup;

    /**
     * @var int
     */
    protected $position = -1;

    /**
     * @var URLParameter
     */
    protected $urlparam;

    public function __construct(GalleryView $view)
    {
        parent::__construct();
        $this->view = $view;
        $this->action = new Action();
        $this->image_popup = new ImagePopup();
        $this->image_popup->setClassName("image_slot");
        $this->actions = new ActionCollection();
        $this->image_popup->setAttribute("relation", "GalleryViewItem");
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);

        if (isset($data["position"])) {
            $this->setAttribute("position", $data["position"]);
        }

        $photoID = (int)$data[$this->view->getBean()->key()];

        $this->setAttribute("itemID", $photoID);

        if ($this->urlparam) {
            $this->setAttribute("ref_key", $this->urlparam->name());
            $this->setAttribute("ref_id", $this->urlparam->value());
        }

        $this->image_popup->setID($photoID);
        $this->image_popup->setBeanClass(get_class($this->view->getBean()));

        $tooltip = "";

        if (isset($data["caption"]) && strlen($data["caption"]) > 0) {
            $this->image_popup->setAttribute("caption", $data["caption"]);
            $tooltip .= tr("Caption") . ": " . $data["caption"] . "<BR>";
        }
        else {
            $this->image_popup->clearAttribute("caption");
        }

        if (isset($data["date_upload"])) {
            $tooltip .= tr("Upload Date") . ": " . dateFormat($data["date_upload"], TRUE);
        }

        $this->image_popup->setAttribute("tooltip", $tooltip);



        if (isset($data["position"])) {
            $this->position = $data["position"];
        }

        $this->actions->setData($data);

    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->image_popup->setPhotoSize($width, $height);
    }

    public function getPhotoWidth(): int
    {
        return $this->image_popup->getPhotoWidth();
    }

    public function getPhotoHeight(): int
    {
        return $this->image_popup->getPhotoHeight();
    }

    public function setActions(ActionCollection $actions)
    {
        $this->actions = $actions;
    }

    public function getActions(): ?ActionCollection
    {
        return $this->actions;
    }

    protected function renderImpl()
    {

        echo "<div class='header'>";

        if ($this->position) {
            echo "<div class='position'>";
            echo "<span>#$this->position</span>";
            echo "</div>";
        }

        echo "<div class='item_actions'>";

        $edit = $this->actions->getByAction("Edit");
        if ($edit instanceof Action) {
            $edit->render();
        }

        $pipe = new PipeSeparator();
        $pipe->render();

        $delete = $this->actions->getByAction("Delete");
        if ($delete instanceof Action) {
            $delete->render();
        }

        echo "</div>"; // item actions

        echo "</div>"; //header

        $this->image_popup->render();

        echo "<div class='footer'>";

        $this->renderFooterAction("First");
        $this->renderFooterAction("Previous");
        $this->renderFooterAction("Next");
        $this->renderFooterAction("Last");

        echo "</div>";

    }

    public function renderFooterAction(string $action)
    {
        $item = $this->actions->getByAction($action);
        if ($item instanceof Action) {
            $item->setContents("");
            $item->render();
        }
    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }


}
