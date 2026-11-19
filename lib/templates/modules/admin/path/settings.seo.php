<?php
include_once("forms/SEOConfigForm.php");
include_once("templates/content/ConfigEditor.php");

//the SEO settings of the site - the default description of the pages (SparkPage)
$config = ConfigEditor::Configure("seo", SEOConfigForm::class);
$config->title = tr("SEO");
