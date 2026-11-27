<?php
//the site copy of a feature path file - it replaces the one of the feature
include_once("templates/content/Plain.php");
$config = Plain::Configure("Site Copy", "");
$config->requireAuth = false;
