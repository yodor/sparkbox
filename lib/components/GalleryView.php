<?php
include_once("beans/DBTableBean.php");
include_once("components/TableView.php");
include_once("components/ItemView.php");

include_once("components/renderers/items/GalleryViewItem.php");
include_once("components/renderers/cells/ImageCellRenderer.php");
include_once("components/renderers/IPhotoRenderer.php");

class GalleryView extends Container
{
    /**
     * @var DBTableBean
     */
    protected $bean;

    protected $refkey = "";
    protected $refval = -1;

    protected $view;

    protected $edit_script;

    protected $photo_renderer;

    const MODE_GRID = 1;
    const MODE_LIST = 2;

    protected $view_mode = GalleryView::MODE_GRID;

    protected $actions;

    public function __construct(DBTableBean $bean)
    {
        parent::__construct();
        $this->bean = $bean;


        $qry = $this->bean->query();

        $qry->select->fields()->set($this->bean->key(), "position", "caption", "date_upload");

        if (strcmp_isset("view", "list")) {

            $view = new TableView($qry);
            $this->view = $view;

            $view->addColumn(new TableColumn("position", "#"));
            $view->addColumn(new TableColumn("photo", "Photo"));
            $view->addColumn(new TableColumn("caption", "Caption"));
            $view->addColumn(new TableColumn("date_upload", "Date Upload"));

            $renderer = new ImageCellRenderer();

            $this->photo_renderer = $renderer;

            $view->getColumn("photo")->setCellRenderer($renderer);

            $view->addColumn(new TableColumn("actions", "Actions"));

            $act = new ActionsCellRenderer();
            $this->actions = $act->getActions();

            $view->getColumn("actions")->setCellRenderer($act);

            $this->view_mode = GalleryView::MODE_LIST;
        }
        else {

            $view = new ItemView($qry);
            $this->view = $view;

            $renderer = new GalleryViewItem($this);
            $renderer->setPhotoSize(256, -1);

            $this->photo_renderer = $renderer;

            $this->actions = $renderer->getActions();

            $view->setItemRenderer($renderer);

            $this->view_mode = GalleryView::MODE_GRID;

        }

        if ($this->bean instanceof OrderedDataBean) {
            $view->setDefaultOrder(" position ASC ");
        }

        $view->getTopPaginator()->view_modes_enabled = TRUE;

        $this->initActions();

        $this->wrapper_enabled = false;

        $this->append($this->view);

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

    public function getPhotoRenderer(): IPhotoRenderer
    {
        return $this->photo_renderer;
    }

    public function getViewMode()
    {
        return $this->view_mode;
    }

    public function getItemActions(): ?ActionCollection
    {
        return $this->actions;
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

        //default mode for action is to keep the request search parameters

        $edit_params = array(new DataParameter("editID", $bkey));

        $collection = $this->actions;
        $collection->append(new Action("Edit", "add.php", $edit_params));

        $collection->append(new PipeSeparator());

        $delete_params = array(new DataParameter("item_id", $bkey));

        $collection->append(new Action("Delete", "?cmd=delete_item", $delete_params));

        if ($this->bean instanceof OrderedDataBean) {

            $collection->append(new RowSeparator());
            $collection->append(new RowSeparator());

            $repos_param = array(new DataParameter("item_id", $bkey),
                                 new DataParameter("#" . get_class($this->bean) . ".%$bkey%", $bkey));

            //if (strlen($this->refkey > 0)) $repos_param[] = $ref_param;

            $collection->append(new Action("First", "?cmd=reposition&type=first", $repos_param));

            $collection->append(new PipeSeparator());

            $collection->append(new Action("Last", "?cmd=reposition&type=last", $repos_param));

            $collection->append(new RowSeparator());

            $collection->append(new Action("Previous", "?cmd=reposition&type=previous", $repos_param));

            $collection->append(new PipeSeparator());

            $collection->append(new Action("Next", "?cmd=reposition&type=next", $repos_param));

            $collection->append(new RowSeparator());

            $collection->append(new Action("Reposition", "?cmd=reposition&type=fixed", $repos_param));
        }
    }

    /**
     * @return TableView|ItemView
     */
    public function getView()
    {
        return $this->view;
    }


}

?>