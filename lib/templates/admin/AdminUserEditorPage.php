<?php
include_once("templates/admin/BeanEditorPage.php");
include_once("forms/AdminUserForm.php");
include_once("beans/AdminUsersBean.php");
include_once("auth/AdminAuthenticator.php");

class AdminUserScript extends PageScript
{
    public function code() : string
    {
        return <<<JS
        function checkForm(frm) {
                try {

                    if (frm.pass.value.length > 0 || frm.pass1.value.length > 0) {
                        if (frm.pass.value != frm.pass1.value) {
                            throw "Passwords do not match";
                        }
                        if (frm.pass.value.length < 6) {
                            throw "Minimum password length is 6";
                        }
                    }

                    frm.password_hash.value = hex_md5(frm.pass.value);

                    frm.pass.value = "";
                    frm.pass1.value = "";
                    return true;

                } catch (e) {
                    alert(e);
                }
                return false;
        }
        
        function toggleRoles() {
                const roles = document.querySelector(".InputComponent[field='role']");
                const access = document.querySelector(".InputComponent [name='access_level']");

                if (access.value == "Full Access") {
                    roles.style.display = "none";
                } else {
                    roles.style.display = "";
                }
        }

        onPageLoad(function () {
            toggleRoles();
        });
JS;

    }
}
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