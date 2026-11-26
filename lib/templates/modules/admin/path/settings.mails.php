<?php
include_once("templates/modules/admin/content/MailPreviewsContent.php");

//every mail of the site as it is sent, with a test send to the administrator (MailPreviews)
$config = MailPreviewsContent::Configure();
$config->title = tr("Mails");
