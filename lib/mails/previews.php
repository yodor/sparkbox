<?php
/**
 * The core mails for the admin preview (MailPreviews) - sample data
 */
include_once("mailers/ForgotPasswordMailer.php");
include_once("mailers/ContactRequestMailer.php");
include_once("utils/url/URL.php");
include_once("mailers/MailPreviews.php");

//the sender of every mail, how the mails leave the site, the enquiries of a core site
$senderName = trim((string)Spark::Get(Config::DEFAULT_SERVICE_NAME));
$senderEmail = trim((string)Spark::Get(Config::DEFAULT_SERVICE_EMAIL));
MailPreviews::SetAddress("sender", tr("Sender"), ($senderName !== "" && $senderEmail !== "") ? "$senderName <$senderEmail>" : $senderEmail,
    "config/settings.php: DEFAULT_SERVICE_NAME, DEFAULT_SERVICE_EMAIL");
MailPreviews::SetAddress("transport", tr("Sent with"), Spark::GetBoolean(Config::PHPMAILER_ENABLED) ? tr("SMTP (PHPMailer)") : tr("PHP mail()"),
    "config/settings.php: PHPMAILER_ENABLED, config/mailer.php");
MailPreviews::SetAddress("enquiries", tr("Enquiries (the contact form)"), $senderEmail, "config/settings.php: DEFAULT_SERVICE_EMAIL");

return array(
    "forgot_password" => array(tr("Admin - new password"), function () : Mailer {
        return new ForgotPasswordMailer("admin@example.com", "kT7#pq2Lm", new URL(Spark::Get(Config::LOCAL) . "/admin/login"));
    }),
    "contact_request" => array(tr("Enquiry from the site"), function () : Mailer {
        $mailer = new ContactRequestMailer((string)Spark::Get(Config::DEFAULT_SERVICE_EMAIL));
        $mailer->setReplyTo("client@example.com", "Client");
        $mailer->setValues(array(tr("Name") => "Ivan Petrov", tr("Phone") => "0888 123 456", tr("Query") => "Sample enquiry text\nsecond line"));
        $mailer->prepareMessage();
        return $mailer;
    }),
);
