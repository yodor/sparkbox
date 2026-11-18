<?php
include_once("class/templates/modules/routetest/content/RouteFailingContent.php");
//test: a content that fails
$config = RouteFailingContent::Configure();
$config->requireAuth = false;
