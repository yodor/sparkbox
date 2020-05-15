<?php
include_once("components/renderers/items/DataIteratorItem.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/ItemView.php");
include_once("components/Action.php");

class GalleryViewItem extends DataIteratorItem implements IActionsCollection, IPhotoRenderer
{

    protected $actions = array();

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
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Action.css";
        return $arr;
    }

    public function setData(array $item)
    {
        parent::setData($item);

        if (isset($item["position"])) {
            $this->setAttribute("position", $item["position"]);
        }

        $photoID = (int)$item[$this->view->getBean()->key()];

        $this->setAttribute("itemID", $photoID);

        if ($this->urlparam) {
            $this->setAttribute("ref_key", $this->urlparam->name());
            $this->setAttribute("ref_id", $this->urlparam->value());
        }

        $this->image_popup->setID($photoID);
        $this->image_popup->setBeanClass(get_class($this->view->getBean()));

        $tooltip = tr("Upload Date") . ": " . dateFormat($item["date_upload"], TRUE);

        if (isset($item["caption"])) {
            $this->image_popup->setAttribute("caption", $item["caption"]);
            $tooltip .= tr("Caption") . ": " . $item["caption"] . "<BR>";
        }

        $this->image_popup->setAttribute("tooltip", $tooltip);

        $this->image_popup->setName($this->image_popup->getBeanClass() . "." . $this->image_popup->getID());

        if (isset($item["position"])) {
            $this->position = $item["position"];
        }

        $actions = array_keys($this->actions);
        foreach ($actions as $idx => $contents) {
            $action = $this->getAction($contents);
            $action->setData($item);
        }

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

    public function addAction(Action $a)
    {
        $this->actions[$a->getContents()] = $a;
    }

    public function getAction(string $contents): Action
    {
        return $this->actions[$contents];
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

        echo "<div class='header'>";

        if ($this->position) {
            echo "<div class='position'>";
            echo "<span>#$this->position</span>";
            echo "</div>";
        }

        echo "<div class='item_actions'>";

        if (isset($this->actions["Edit"])) {
            $this->actions["Edit"]->render();
        }

        $pipe = new PipeSeparator();
        $pipe->render();

        if (isset($this->actions["Delete"])) {
            $this->actions["Delete"]->render();
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

    public function renderFooterAction(string $title)
    {
        if (!isset($this->actions[$title])) return;

        $action = $this->actions[$title];

        $action->setContents("");
        $action->render();

    }

    public function renderSeparator($idx_curr, $items_total)
    {

    }

    /**
     * @param URLParameter $urlparam
     * @return void
     */
    public function addURLParameter(URLParameter $param)
    {
        //TODO: implement actions
        $this->urlparam = $param;
    }
}