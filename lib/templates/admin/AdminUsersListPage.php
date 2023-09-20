<?php
include_once("templates/admin/BeanListPage.php");

include_once("beans/AdminUsersBean.php");

include_once("responders/DeleteItemResponder.php");
include_once("responders/ToggleFieldResponder.php");
include_once("components/renderers/cells/DateCellRenderer.php");
include_once("components/renderers/cells/NumericCellRenderer.php");

class AdminUsersListPage extends BeanListPage
{

    public function __construct()
    {
        parent::__construct();


    }

    public function initView()
    {
        $bean = new AdminUsersBean();

        $h_delete = new DeleteItemResponder($bean);

        $h_toggle = new ToggleFieldResponder($bean);


        $qry = $bean->query();
        $qry->select->fields()->set( "userID", "email", "fullname", "date_created", "last_active", "counter", "suspend");
        $qry->select->fields()->setExpression("(SELECT concat('|', role) FROM admin_access ac WHERE ac.userID=userID)", "access_level");

        $view = new TableView($qry);

        //TODO: selective enable bottom or top
        $view->enablePaginators(TableView::PAGINATOR_BOTTOM);

        $view->addColumn(new TableColumn($bean->key(), "ID", TableColumn::ALIGN_CENTER));
        $view->addColumn(new TableColumn("email", "Email"));
        $view->addColumn(new TableColumn("fullname", "Full Name"));

        $view->addColumn(new TableColumn("date_created", "Date Created", TableColumn::ALIGN_CENTER));
        $view->getColumn("date_created")->setCellRenderer(new DateCellRenderer());

        $view->addColumn(new TableColumn("last_active", "Last Active", TableColumn::ALIGN_CENTER));
        $view->getColumn("last_active")->setCellRenderer(new DateCellRenderer());

        $view->addColumn(new TableColumn("access_level", "Access"));

        $view->addColumn(new TableColumn("counter", "Login Count"));
        $view->getColumn("counter")->setCellRenderer(new NumericCellRenderer("%0.0f"));

        $view->addColumn(new TableColumn("status", "Availability"));
        $view->addColumn(new TableColumn("actions", "Actions"));

        //$view->getColumn("access_level")->setCellRenderer(new CallbackTableCellRenderer("draw_access_level"));

        $act = new ActionsCellRenderer();
        $act->getActions()->append(new Action("Edit", "add.php", array(new DataParameter("editID", $bean->key()))));
        $act->getActions()->append(new PipeSeparator());
        $act->getActions()->append($h_delete->createAction());

        $view->getColumn("actions")->setCellRenderer($act);

        $vis_act = new ActionsCellRenderer();
        $check_is_suspend = function (Action $act, array $data) {
            return ($data['suspend'] < 1);
        };
        $check_is_not_suspend = function (Action $act, array $data) {
            return ($data['suspend'] > 0);
        };
        $vis_act->getActions()->append($h_toggle->createAction("Disable", "field=suspend&status=1", $check_is_suspend));
        $vis_act->getActions()->append($h_toggle->createAction("Enable", "field=suspend&status=0", $check_is_not_suspend));
        $view->getColumn("status")->setCellRenderer($vis_act);

        $this->append($view);

        $this->view = $view;

        return $this->view;
    }
}