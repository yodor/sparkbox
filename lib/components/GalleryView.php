<?php
include_once("beans/DBTableBean.php");
include_once("components/TableView.php");
include_once("components/ItemView.php");

include_once("components/renderers/items/GalleryViewItem.php");
include_once("components/renderers/cells/ImageCell.php");
include_once("components/renderers/cells/DateCell.php");

include_once("components/renderers/IPhotoRenderer.php");

class GalleryView extends Container
{
    /**
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = null;

    protected ?AbstractResultView $view = null;

    const int MODE_GRID = 1;
    const int MODE_LIST = 2;

    protected int $view_mode = GalleryView::MODE_GRID;

    protected ?ActionCollection $actions = null;


    public function __construct(DBTableBean $bean, ?SQLQuery $query=NULL)
    {
        parent::__construct();

        $this->bean = $bean;

        if (!is_null($query)) {
            $qry = $query;
        }
        else {
            $qry = $this->bean->query($this->bean->key(), "position", "caption", "date_upload");
        }

        if (Spark::strcmp_isset("view", "list")) {

            $view = new TableView($qry);
            $view->addClassName("GalleryView");
            $view->addClassName("ListMode");

            $view->addColumn(new TableColumn("position", "#"));
            $view->addColumn(new TableColumn("photo", "Photo"));
            $view->addColumn(new TableColumn("caption", "Caption"));
            $view->addColumn(new TableColumn("date_upload", "Date Upload"));

            $renderer = new ImageCell();

            $view->getColumn("photo")->setCellRenderer($renderer);
            $view->getColumn("date_upload")->setCellRenderer(new DateCell());

            $view->addColumn(new TableColumn("actions", "Actions"));

            $act = new ActionsCell();
            $this->actions = $act->getActions();

            $view->getColumn("actions")->setCellRenderer($act);

            $this->view_mode = GalleryView::MODE_LIST;
        }
        else {

            $view = new ItemView($qry);

            $view->addClassName("GalleryView");
            $view->addClassName("GridMode");

            $renderer = new GalleryViewItem($this);
            $renderer->setPhotoSize(256, -1);

            $this->actions = $renderer->getActions();

            $view->setItemRenderer($renderer);

            $this->view_mode = GalleryView::MODE_GRID;

        }

        $this->view = $view;

        if ($this->bean instanceof OrderedDataBean) {
            $view->setDefaultOrder(" position ASC ");
        }

        $view->getHeader()->getViewMode()->setRenderEnabled(true);

        $this->initActions();

        $this->wrapper_enabled = false;

        $this->items()->append($this->view);

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/GalleryView.css";
        return $arr;
    }

    public function getViewMode() : int
    {
        return $this->view_mode;
    }

    public function getItemActions(): ?ActionCollection
    {
        return $this->actions;
    }

    /**
     * @return DBTableBean
     */
    public function getBean() : DBTableBean
    {
        return $this->bean;
    }

    protected function initActions() : void
    {

        $url = URL::Current();
        $url->setScriptName("add.php");
        $url->add(new DataParameter("editID", $this->bean->key()));
        $action_edit = new Action("Edit");
        $action_edit->setURL($url);
        $this->actions->append($action_edit);

        $this->actions->append(Action::PipeSeparator());

        $url = URL::Current();
        $url->add(new URLParameter(RequestResponder::KEY_COMMAND, DeleteItemResponder::class));
        $url->add(new DataParameter("item_id", $this->bean->key()));
        $delete_action = new Action("Delete");
        $delete_action->setURL($url);
        $this->actions->append($delete_action);


        if ($this->bean instanceof OrderedDataBean) {

            $this->actions->append(Action::RowSeparator());

            $url = URL::Current();
            $url->add(new URLParameter(RequestResponder::KEY_COMMAND, ChangePositionResponder::class));
            $url->add(new DataParameter("item_id", $this->bean->key()));
            $url->add(new DataParameter("#" . get_class($this->bean) . ".%{$this->bean->key()}%", $this->bean->key()));

            $action = new Action("First");
            $action->setURL(clone $url);
            $action->getURL()->add(new URLParameter("type", "first"));
            $this->actions->append($action);

            $this->actions->append(Action::PipeSeparator());

            $action = new Action("Last");
            $action->setURL(clone $url);
            $action->getURL()->add(new URLParameter("type", "last"));
            $this->actions->append($action);

            $this->actions->append(Action::RowSeparator());

            $action = new Action("Previous");
            $action->setURL(clone $url);
            $action->getURL()->add(new URLParameter("type", "previous"));
            $this->actions->append($action);

            $this->actions->append(Action::PipeSeparator());

            $action = new Action("Next");
            $action->setURL(clone $url);
            $action->getURL()->add(new URLParameter("type", "next"));
            $this->actions->append($action);

            $this->actions->append(Action::RowSeparator());

            $action = new Action("Fixed");
            $action->setURL(clone $url);
            $action->getURL()->add(new URLParameter("type", "fixed"));
            $this->actions->append($action);

        }
    }

    /**
     * @return AbstractResultView
     */
    public function getView() : AbstractResultView
    {
        return $this->view;
    }


}