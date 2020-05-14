<?php
include_once("components/Component.php");
include_once("components/ItemView.php");
include_once("components/renderers/IItemRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("components/renderers/ActionRenderer.php");

class GalleryViewItemRenderer extends Component implements IItemRenderer, IActionsCollection, IPhotoRenderer
{

    protected $actions = array();

    protected $item = NULL;

    /**
     * @var GalleryView
     */
    protected $view;

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

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
        $this->actionRenderer = new ActionRenderer();
        $this->image_popup = new ImagePopup();
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

        if (isset($item["position"])) {
            $this->setAttribute("position", $item["position"]);
        }

        $photoID = (int)$this->item[$this->view->getBean()->key()];

        $this->setAttribute("itemID", $photoID);

        if ($this->urlparam) {
            $this->setAttribute("ref_key", $this->urlparam->name());
            $this->setAttribute("ref_id", $this->urlparam->value());
        }

        $this->image_popup->setID($photoID);
        $this->image_popup->setBeanClass(get_class($this->view->getBean()));

        $tooltip = tr("Caption") . ": " . $this->item["caption"]."<BR>";
        $tooltip .= tr("Upload Date") . ": " . dateFormat($this->item["date_upload"], TRUE);

        $this->image_popup->setAttribute("tooltip", $tooltip);
        $this->image_popup->setAttribute("caption", $item["caption"]);

        $this->image_popup->setName($this->image_popup->getBeanClass() . "." . $this->image_popup->getID());

        if (isset($item["position"])) {
            $this->position = $item["position"];
        }


    }

    public function getItem()
    {
        return $this->item;
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

        echo "<div class='header'>";

        if ($this->position) {
            echo "<div class='position'>";
            echo "<span>#$this->position</span>";
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

        $this->actionRenderer->render_title = FALSE;

        $this->actionRenderer->setAction($this->actions[$title]);
        $this->actionRenderer->setData($this->item);
        $this->actionRenderer->render();

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