<?php
include_once("templates/admin/BeanListPage.php");

include_once("beans/AdminUsersBean.php");

include_once("handlers/DeleteItemRequestHandler.php");
include_once("handlers/ToggleFieldRequestHandler.php");

class AdminUsersListPage extends BeanListPage
{

    public function __construct()
    {
        parent::__construct();

        //TODO
        //$this->page->checkAccess($access_role);

        $this->page->setName("Administrative Users");

    }

    protected function initPageActions()
    {
        parent::initPageActions();

        $action_add = new Action("", "add.php");
        $action_add->setAttribute("action", "add");
        $action_add->setAttribute("title", "Add Item");
        $this->page->addAction($action_add);
    }

    public function initView()
    {
        $bean = new AdminUsersBean();
        $h_delete = new DeleteItemRequestHandler($bean);
        RequestController::addRequestHandler($h_delete);
        $h_toggle = new ToggleFieldRequestHandler($bean);
        RequestController::addRequestHandler($h_toggle);

        $qry = $bean->query();
        $qry->select->fields =  " userID, email, fullname, date_created, last_active, counter, suspend, (SELECT concat('|', role) FROM admin_access ac WHERE ac.userID=userID) as access_level ";

        $view = new TableView($qry);

        //TODO: selective enable bottom or top
        //$view->enablePaginators(false);

        $view->addColumn(new TableColumn($bean->key(), "ID"));
        $view->addColumn(new TableColumn("email", "Email"));
        $view->addColumn(new TableColumn("fullname", "Full Name"));
        $view->addColumn(new TableColumn("date_created", "Date Created"));
        $view->addColumn(new TableColumn("last_active", "Last Active"));
        $view->addColumn(new TableColumn("access_level", "Access"));
        $view->addColumn(new TableColumn("counter", "Login Count"));
        $view->addColumn(new TableColumn("status", "Availability"));
        $view->addColumn(new TableColumn("actions", "Actions"));

        //$view->getColumn("access_level")->setCellRenderer(new CallbackTableCellRenderer("draw_access_level"));

        $act = new ActionsTableCellRenderer();
        $act->addAction(new Action("Edit", "add.php", array(new DataParameter("editID", $bean->key()))));
        $act->addAction(new PipeSeparator());
        $act->addAction($h_delete->createAction());

        $view->getColumn("actions")->setCellRenderer($act);

        $vis_act = new ActionsTableCellRenderer();
        $check_is_suspend = function (Action $act, array $data) {
            return ($data['suspend'] < 1);
        };
        $check_is_not_suspend = function (Action $act, array $data) {
            return ($data['suspend'] > 0);
        };
        $vis_act->addAction($h_toggle->createAction("Disable", "field=suspend&status=1", $check_is_suspend));
        $vis_act->addAction($h_toggle->createAction("Enable", "field=suspend&status=0", $check_is_not_suspend));
        $view->getColumn("status")->setCellRenderer($vis_act);

        $this->append($view);
    }
}