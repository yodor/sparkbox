<?php
//test: a content without a title
include_once("class/templates/modules/routetest/content/RouteFallbackContent.php");
$config = RouteFallbackContent::Configure();
$config->requireAuth = false;
