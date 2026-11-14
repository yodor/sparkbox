<?php
include_once("beans/AdminUsersBean.php");
include_once("beans/AdminAccessBean.php");
include_once("forms/AdminUserForm.php");
include_once("auth/AdminAuthenticator.php");
include_once("components/AdminUserScript.php");
include_once("responders/ToggleFieldResponder.php");
include_once("components/renderers/cells/DateCell.php");
include_once("components/renderers/cells/NumericCell.php");
include_once("components/renderers/cells/ActionsCell.php");

if (URL::Current()->contains("editID")) {

    $config = TemplateConfig::Editor(AdminUsersBean::class, AdminUserForm::class);
    $config->title = tr("Administrator");

    $config->observer = function (TemplateEvent $event) {
        $content = $event->getSource();
        if (!($content instanceof BeanEditor)) throw new Exception("Incorrect event source - expecting BeanEditor");

        if ($event->isEvent(TemplateEvent::CONTENT_SETUP)) {
            //roles = top level menu sections
            $roles = array();
            foreach (SparkTemplateAdminPage::LoadMenu()?->toArray() ?? array() as $item) {
                if ($item instanceof MenuItem) $roles[] = $item->getName();
            }
            $form = $content->getForm();
            if ($form instanceof AdminUserForm) $form->setRoles($roles);
        }
        else if ($event->isEvent(TemplateEvent::CONTENT_INITIALIZED)) {
            $editor = $content->editor();
            $editor->getTransactor()->assignInsertValue("context", AdminAuthenticator::CONTEXT_NAME);
            $editor->getForm()->getRenderer()->setAttribute("onSubmit", "return checkForm(this)");
            new AdminUserScript();
        }
    };
}
else {

    $bean = new AdminUsersBean();
    //the roles column reads admin_access - create it on a fresh install
    new AdminAccessBean();
    $query = $bean->query("userID", "email", "fullname", "access_level", "date_created", "last_active", "counter", "suspend");
    $query->stmt->alias("(SELECT group_concat(ac.role SEPARATOR '; ') FROM admin_access ac WHERE ac.userID=admin_users.userID)", "roles");

    $config = TemplateConfig::List(AdminUsersBean::class);
    $config->title = tr("Administrators");
    $config->iterator = $query;
    $config->listFields = array("email" => "Email", "fullname" => "Full Name", "date_created" => "Date Created",
        "last_active" => "Last Active", "access_level" => "Access", "roles" => "Roles", "counter" => "Login Count",
        "suspend" => "Availability");
    $config->searchField = array("email", "fullname");
    $config->clearNavigation = true;

    $config->observer = function (TemplateEvent $event) {
        if (!$event->isEvent(TemplateEvent::CONTENT_INITIALIZED)) return;
        $content = $event->getSource();
        if (!($content instanceof BeanList)) throw new Exception("Incorrect event source - expecting BeanList");

        $view = $content->tableView();
        $view->getColumn("date_created")->setCellRenderer(new DateCell());
        $view->getColumn("last_active")->setCellRenderer(new DateCell());
        $view->getColumn("counter")->setCellRenderer(new NumericCell("%0.0f"));

        //suspend / enable the account
        $toggle = new ToggleFieldResponder($content->getBean());
        $cell = new ActionsCell();
        foreach (array(array("Disable", "1", fn(Action $a, array $d) => ($d["suspend"] < 1)),
                     array("Enable", "0", fn(Action $a, array $d) => ($d["suspend"] > 0))) as [$title, $status, $check]) {
            $action = $toggle->createAction($title);
            $action->setURL(Module::PathURL("", $action->getURL()));
            $action->getURL()->add(new URLParameter("field", "suspend"));
            $action->getURL()->add(new URLParameter("status", $status));
            $action->setCheckCode($check);
            $cell->getActions()->append($action);
        }
        $view->getColumn("suspend")->setCellRenderer($cell);
    };
}
