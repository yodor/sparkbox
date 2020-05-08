<?php
include_once("beans/DBTableBean.php");
include_once("components/TableView.php");
include_once("components/ListView.php");

include_once("components/renderers/items/GalleryViewItemRenderer.php");
include_once("components/renderers/IActionRenderer.php");
include_once("components/renderers/cells/TableImageCellRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");

class GalleryView extends Component
{
    /**
     * @var DBTableBean
     */
    protected $photos_bean = NULL;

    protected $refkey = "";
    protected $refval = -1;

    protected $view = NULL;

    protected $edit_script;

    protected $iphoto_renderer;

    const MODE_UNINITIALIZED = -1;
    const MODE_GRID = 1;
    const MODE_LIST = 2;

    protected $view_mode = GalleryView::MODE_UNINITIALIZED;


    public $blob_field = "item_photo";


    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/GalleryView.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/GalleryView.js";
        return $arr;
    }

    public function getViewMode()
    {
        return $this->view_mode;

    }

    public function getRefKey()
    {
        return $this->refkey;
    }

    public function getRefVal()
    {
        return $this->refval;
    }

    /**
     * @return DBTableBean|null
     */
    public function getBean()
    {
        return $this->photos_bean;
    }

    public function getEditScript()
    {
        return $this->edit_script;
    }

    public function initView(DBTableBean $bean, $edit_script = "gallery_add.php", $refkey = "", $refval = -1)
    {
        $bkey = $bean->key();

        $this->photos_bean = $bean;
        $this->refkey = $refkey;
        $this->refval = $refval;
        $this->edit_script = $edit_script;

        $view = false;
        if (strcmp_isset("view", "list")) {

            $view = new TableView($bean->query());
            $this->view = $view;

            //   $view->addColumn(new TableColumn("ppID", "ID"));

            if ($this->photos_bean instanceof OrderedDataBean) {
                $view->addColumn(new TableColumn("position", "#"));
            }
            $view->addColumn(new TableColumn($this->blob_field, "Photo"));
            $view->addColumn(new TableColumn("caption", "Caption"));
            $view->addColumn(new TableColumn("date_upload", "Date Upload"));

            $photos_class = get_class($bean);
            $photos_bean = new $photos_class;

            $renderer = new TableImageCellRenderer($photos_bean, -1, 128);

            $view->getColumn($this->blob_field)->setCellRenderer($renderer);
            $view->getColumn($this->blob_field)->getHeaderCellRenderer()->setSortable(false);

            $view->addColumn(new TableColumn("actions", "Actions"));

            $act = new ActionsTableCellRenderer();

            $this->initActions($act);

            $view->getColumn("actions")->setCellRenderer($act);
            $this->iphoto_renderer = $renderer;
            $this->view_mode = GalleryView::MODE_LIST;
        }
        else {
            $view = new ListView($bean->query());
            $this->view = $view;

            $renderer = new GalleryViewItemRenderer($this);
            $renderer->setPhotoSize(-1, 256);

            $this->setGridItemRenderer($renderer);

            $view->items_per_page = 9;

            $this->iphoto_renderer = $renderer;
            $this->view_mode = GalleryView::MODE_GRID;

        }


        if ($this->photos_bean instanceof OrderedDataBean) {
            $view->setDefaultOrder(" position ASC ");
        }

        $view->getTopPaginator()->view_modes_enabled = true;
        $view->getTopPaginator()->setCaption($this->caption);


    }

    public function setGridItemRenderer(GalleryViewItemRenderer $r)
    {
        $this->initActions($r);

        $this->view->setItemRenderer($r);
    }

    protected function initActions(IActionsCollection $act)
    {
        $bkey = $this->photos_bean->key();

        $ref_param = new ActionParameter($this->refkey, $this->refkey);

        $edit_params = array(new ActionParameter("editID", $bkey));
        if (strlen($this->refkey > 0)) $edit_params[] = $ref_param;

        $act->addAction(new Action("Edit", $this->edit_script, $edit_params));

        $act->addAction(new PipeSeparatorAction());

        $delete_params = array(new ActionParameter("item_id", $bkey));
        if (strlen($this->refkey > 0)) $delete_params[] = $ref_param;

        $act->addAction(new Action("Delete", "?cmd=delete_item", $delete_params));

        if ($this->photos_bean instanceof OrderedDataBean) {

            $act->addAction(new RowSeparatorAction());
            $act->addAction(new RowSeparatorAction());

            $repos_param = array(new ActionParameter("item_id", $bkey), new ActionParameter("#" . get_class($this->photos_bean) . ".%$bkey%", "", true));

            if (strlen($this->refkey > 0)) $repos_param[] = $ref_param;

            $act->addAction(new Action("First", "?cmd=reposition&type=first", $repos_param));
            $act->addAction(new PipeSeparatorAction());
            $act->addAction(new Action("Last", "?cmd=reposition&type=last", $repos_param));
            $act->addAction(new RowSeparatorAction());
            $act->addAction(new Action("Previous", "?cmd=reposition&type=previous", $repos_param));
            $act->addAction(new PipeSeparatorAction());
            $act->addAction(new Action("Next", "?cmd=reposition&type=next", $repos_param));

            $act->addAction(new RowSeparatorAction());

            $act->addAction(new Action("Reposition", "javascript:choosePosition(\"%$bkey%\",{$this->refval})", array(new ActionParameter($bkey, $bkey))));
        }
    }

    public function getView()
    {
        return $this->view;
    }

    public function startRender()
    {

    }

    public function finishRender()
    {

    }



    public function renderImpl()
    {
        $this->view->render();

    }


}

?>