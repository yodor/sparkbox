<?php
include_once("beans/DBTableBean.php");
include_once("components/TableView.php");
include_once("components/ItemView.php");

include_once("components/renderers/items/GalleryViewItemRenderer.php");
include_once("components/renderers/cells/TableImageCellRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");

class GalleryView extends Component
{
    /**
     * @var DBTableBean
     */
    protected $bean = NULL;

    protected $refkey = "";
    protected $refval = -1;

    protected $view = NULL;

    protected $edit_script;

    protected $photo_renderer;

    const MODE_GRID = 1;
    const MODE_LIST = 2;

    protected $view_mode = GalleryView::MODE_GRID;

    protected $actionsCollection;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct();
        $this->bean = $bean;

        $fields = array($this->bean->key(), "position", "caption", "date_upload");

        $qry = $this->bean->query();
        $qry->select->fields = implode(",", $fields);

        if (strcmp_isset("view", "list")) {

            $view = new TableView($qry);
            $this->view = $view;

            $view->addColumn(new TableColumn("position", "#"));
            $view->addColumn(new TableColumn("photo", "Photo"));
            $view->addColumn(new TableColumn("caption", "Caption"));
            $view->addColumn(new TableColumn("date_upload", "Date Upload"));

            $renderer = new TableImageCellRenderer();
            $renderer->setBean($this->bean);

            $this->photo_renderer = $renderer;

            $view->getColumn("photo")->setCellRenderer($renderer);
            $view->getColumn("photo")->getHeaderCellRenderer()->setSortable(FALSE);

            $view->addColumn(new TableColumn("actions", "Actions"));

            $this->actionsCollection = new ActionsTableCellRenderer();

            $view->getColumn("actions")->setCellRenderer($this->actionsCollection);

            $this->view_mode = GalleryView::MODE_LIST;
        }
        else {

            $view = new ItemView($qry);
            $this->view = $view;

            $renderer = new GalleryViewItemRenderer($this);
            $renderer->setPhotoSize(-1, 256);

            $this->actionsCollection = $renderer;

            $this->view->setItemRenderer($renderer);

            $this->view_mode = GalleryView::MODE_GRID;

            $this->photo_renderer = $renderer;
        }

        if ($this->bean instanceof OrderedDataBean) {
            $view->setDefaultOrder(" position ASC ");
        }

        $view->getTopPaginator()->view_modes_enabled = TRUE;

    }

    public function getPhotoRenderer() : IPhotoRenderer
    {
        return $this->photo_renderer;
    }

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

    public function getActionsCollection()
    {
        return $this->actionsCollection;
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
        return $this->bean;
    }

    protected function initActions()
    {
        $bkey = $this->bean->key();

        //$ref_param = new ActionParameter($this->refkey, $this->refkey);

        $edit_params = array(new ActionParameter("editID", $bkey));

        //if (strlen($this->refkey > 0)) $edit_params[] = $ref_param;

        $collection = $this->actionsCollection;
        $collection->addAction(new Action("Edit", "add.php", $edit_params));

        $collection->addAction(new PipeSeparatorAction());

        $delete_params = array(new ActionParameter("item_id", $bkey));

        //if (strlen($this->refkey > 0)) $delete_params[] = $ref_param;

        $collection->addAction(new Action("Delete", "?cmd=delete_item", $delete_params));

        if ($this->bean instanceof OrderedDataBean) {

            $collection->addAction(new RowSeparatorAction());
            $collection->addAction(new RowSeparatorAction());

            $repos_param = array(new ActionParameter("item_id", $bkey),
                                 new URLParameter("#" . get_class($this->bean) . ".%$bkey%"));

            //if (strlen($this->refkey > 0)) $repos_param[] = $ref_param;

            $collection->addAction(new Action("First", "?cmd=reposition&type=first", $repos_param));

            $collection->addAction(new PipeSeparatorAction());

            $collection->addAction(new Action("Last", "?cmd=reposition&type=last", $repos_param));

            $collection->addAction(new RowSeparatorAction());

            $collection->addAction(new Action("Previous", "?cmd=reposition&type=previous", $repos_param));

            $collection->addAction(new PipeSeparatorAction());

            $collection->addAction(new Action("Next", "?cmd=reposition&type=next", $repos_param));

            $collection->addAction(new RowSeparatorAction());

            $collection->addAction(new Action("Reposition", "javascript:choosePosition(\"%$bkey%\",{$this->refval})", array(new ActionParameter($bkey, $bkey))));
        }
    }

    /**
     * @return TableView|ItemView
     */
    public function getView()
    {
        return $this->view;
    }

    public function startRender()
    {
        $this->initActions();
    }

    public function finishRender()
    {

    }

    protected function renderImpl()
    {
        $this->view->render();


    }

}

?>