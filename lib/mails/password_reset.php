<?php
/**
 * A password reset link (PasswordResetMailer) - an administrator or a client: link - the reset address, site - the
 * site domain
 * @var MailView $this
 */
echo $this->text(tr("Hello") . ",");
echo $this->text(tr("We received a request to change the password of your profile at %s.", $this->get("site")));
echo $this->text(tr("To set a new password, open the link below. The link is valid for 1 hour and can be used only once."));
echo $this->button($this->get("link"), tr("Change password"));
echo $this->paragraph("<span style='font-size:13px;color:#6b7280;'>" . $this->e(tr("If the button does not open, copy this address into your browser:")) . "<br>"
    . $this->link($this->get("link")) . "</span>");
echo $this->text(tr("If you did not ask for a password change, just ignore this mail - your password stays unchanged."));
echo $this->text(tr("Sincerely") . ",\n" . $this->siteTitle());
