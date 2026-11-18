<?php
include_once("components/renderers/items/DataIteratorItem.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/ItemView.php");
include_once("components/Action.php");
include_once("components/ClosureComponent.php");
include_once("components/ImagePopup.php");

/**
 * One photo of a GalleryView (admin galleries):
 *
 * heading
 *   position ('#n' when the row has a position)
 *   item_actions (Edit | Delete)
 * image_slot (ImagePopup)
 * trailer (First, Previous, Next, Last)
 */

class GalleryViewItem extends DataIteratorItem implements IActionCollection, IPhotoRenderer
{

    protected ActionCollection $actions;

    /**
     * @var GalleryView
     */
    protected GalleryView $view;

    /**
     * @var Action
     */
    protected Action $action;

    /**
     * @var ImagePopup
     */
    protected ImagePopup $image_popup;


    /**
     * @var URLParameter|null
     */
    protected ?URLParameter $urlparam = null;

    protected Container $positionBox;
    protected Component $positionText;

    public function __construct(GalleryView $view)
    {
        parent::__construct();
        $this->setComponentClass("GalleryViewItem");

        $this->view = $view;
        $this->action = new Action();

        $this->image_popup = new ImagePopup();
        $this->image_popup->setClassName("image_slot");
        $this->actions = new ActionCollection();
        $this->image_popup->setAttribute("relation", "GalleryViewItem");

        $heading = new Container(false);
        $heading->setComponentClass("heading");

        $this->positionBox = new Container(false);
        $this->positionBox->setComponentClass("position");
        $this->positionText = new Component(false);
        $this->positionText->setTagName("span");
        $this->positionText->setComponentClass("");
        $this->positionBox->items()->append($this->positionText);
        $heading->items()->append($this->positionBox);

        $itemActions = new ClosureComponent($this->renderItemActions(...), true, false);
        $itemActions->setComponentClass("item_actions");
        $heading->items()->append($itemActions);

        $this->items()->append($heading);
        $this->items()->append($this->image_popup);

        $trailer = new ClosureComponent($this->renderTrailer(...), true, false);
        $trailer->setComponentClass("trailer");
        $this->items()->append($trailer);
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/Action.css";
        return $arr;
    }

    public function setData(array $data): void
    {
        parent::setData($data);


        $photoID = (int)$data[$this->view->getBean()->key()];

        $this->setAttribute("itemID", $photoID);

        if ($this->urlparam) {
            $this->setAttribute("ref_key", $this->urlparam->name());
            $this->setAttribute("ref_id", $this->urlparam->value());
        }

        $this->image_popup->setDataID($photoID);
        $this->image_popup->image()->getStorageItem()->className = get_class($this->view->getBean());

        $tooltip = "";

        if (isset($data["caption"]) && strlen($data["caption"]) > 0) {
            $this->image_popup->setAttribute("caption", $data["caption"]);
            $tooltip .= tr("Caption") . ": " . $data["caption"] . "<BR>";
        } else {
            $this->image_popup->removeAttribute("caption");
        }

        if (isset($data["date_upload"])) {
            $tooltip .= tr("Upload Date") . ": " . Spark::DateFormat($data["date_upload"], TRUE);
        }

        $this->image_popup->setAttribute("tooltip", $tooltip);

        if (isset($data["position"])) {
            $this->position = $data["position"];
        }

        $this->actions->setData($data);

    }

    public function setPhotoSize(int $width, int $height): void
    {
        $this->image_popup->image()->setPhotoSize($width, $height);
    }

    public function getPhotoWidth(): int
    {
        return $this->image_popup->image()->getPhotoWidth();
    }

    public function getPhotoHeight(): int
    {
        return $this->image_popup->image()->getPhotoHeight();
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): ?ActionCollection
    {
        return $this->actions;
    }

    protected function renderImpl(): void
    {
        //'#n' only when the row has a position
        $this->positionBox->setRenderEnabled((bool)$this->position);
        $this->positionText->setText("#" . $this->position);

        parent::renderImpl();
    }

    /**
     * Edit | Delete of the row
     * @return void
     */
    protected function renderItemActions() : void
    {
        $edit = $this->actions->getByAction("Edit");
        if ($edit instanceof Action) {
            $edit->render();
        }

        Action::PipeSeparator()->render();

        $delete = $this->actions->getByAction("Delete");
        if ($delete instanceof Action) {
            $delete->render();
        }
    }

    /**
     * Position change actions of the row
     * @return void
     */
    protected function renderTrailer() : void
    {
        $this->renderFooterAction("First");
        $this->renderFooterAction("Previous");
        $this->renderFooterAction("Next");
        $this->renderFooterAction("Last");
    }

    public function renderFooterAction(string $action): void
    {
        $item = $this->actions->getByAction($action);
        if ($item instanceof Action) {
            $item->setContents("");
            $item->render();
        }
    }
}