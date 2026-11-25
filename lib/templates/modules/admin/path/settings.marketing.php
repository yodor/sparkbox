<?php
include_once("forms/MarketingConfigForm.php");
include_once("templates/content/ConfigEditor.php");

//the tracking IDs and the conversions of the site (Marketing) - the store's path file sets its own form
$config = ConfigEditor::Configure(Marketing::SECTION, MarketingConfigForm::class);
$config->title = tr("Marketing");
