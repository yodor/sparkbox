<?php
include_once("session.php");
include_once("class/pages/AdminPage.php");
include_once("lib/forms/AdminUserInputForm.php");
include_once("lib/beans/AdminUsersBean.php");


$menu = array();


$page = new AdminPage("Add User");
$page->checkAccess(ROLE_ADMIN_USERS_MENU);


$view = new InputFormView(new AdminUsersBean(), new AdminUserInputForm());

$view->getForm()->getRenderer()->setAttribute("onSubmit", "return checkForm(this)");


$view->processInput();


$page->startRender($menu);

$view->render();

?>
    <script type='text/javascript'>
        function toggleRoles() {
            var roles = $(".InputComponent[field='role']");
            var access = $(".InputComponent [name='access_level']");

            if (access.val() == "Full Access") {
                roles.css("display", "none");
            } else {
                roles.css("display", "");
            }
        }

        onPageLoad(function () {
            toggleRoles();
        });
    </script>
    <script type='text/javascript' src='<?php
    echo SITE_ROOT; ?>lib/js/md5.js'></script>
    <script type='text/javascript'>
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
    </script>
<?php
$page->finishRender();
?>