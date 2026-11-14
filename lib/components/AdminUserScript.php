<?php
include_once("components/InlineScript.php");

/**
 * Admin user editor: password repeat check before submit, roles shown only for limited access
 */
class AdminUserScript extends InlineScript implements IPageComponent
{
    protected function finalize() : void
    {
        $code = <<<JS
function checkForm(frm) {
        try {

            if (frm.password.value.length > 0 || frm.password_compare.value.length > 0) {
                if (frm.password.value != frm.password_compare.value) {
                    throw "Passwords do not match";
                }
                if (frm.password.value.length < 6) {
                    throw "Minimum password length is 6";
                }
            }

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
        $this->setCode($code);
        parent::finalize();
    }
}
