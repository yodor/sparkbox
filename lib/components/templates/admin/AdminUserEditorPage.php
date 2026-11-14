<?php
include_once("components/templates/admin/BeanEditorPage.php");
include_once("forms/AdminUserForm.php");
include_once("beans/AdminUsersBean.php");
include_once("auth/AdminAuthenticator.php");
include_once("components/AdminUserScript.php");

class AdminUserEditorPage extends BeanEditorPage
{

    public function __construct()
    {
        parent::__construct();


        //$this->page->checkAccess(ROLE_ADMIN_USERS_MENU);

        $this->setBean(new AdminUsersBean());

        $roles = array();
        $menu = $this->getPage()->getMenuBar()->getMenu();

        $itr = $menu->iterator();
        while ($item = $itr->next()) {
            if ($item instanceof MenuItem) {
                $roles[] = $item->getName();
            }
        }


        $form = new AdminUserForm();
        $form->setRoles($roles);
        $this->setForm($form);
        $this->page->head()->addJS(Spark::Get(Config::SPARK_LOCAL)."/js/md5.js");

        new AdminUserScript();


    }

    public function initView(): ?Component
    {
        parent::initView();

        $this->view->getForm()->getRenderer()->setAttribute("onSubmit", "return checkForm(this)");
        $this->getEditor()->getTransactor()->assignInsertValue("context", AdminAuthenticator::CONTEXT_NAME);

        return $this->view;
    }

}