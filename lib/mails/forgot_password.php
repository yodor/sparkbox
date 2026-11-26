<?php
/**
 * The new password of an administrator (ForgotPasswordMailer): password, login - the login address (optional)
 * @var MailView $this
 */
echo $this->text(tr("Hello") . ",");
echo $this->text(tr("This email is sent in relation to your forgot password request at") . " " . $this->siteTitle() . ".");
echo $this->paragraph($this->e(tr("Your new password is")) . ": <strong style='font-family:Consolas,Menlo,monospace;font-size:16px;'>"
    . $this->e($this->get("password")) . "</strong>");
if ($this->get("login") !== "") {
    echo $this->button($this->get("login"), tr("Log in"));
}
echo $this->text(tr("Sincerely") . ",\n" . $this->siteTitle());
