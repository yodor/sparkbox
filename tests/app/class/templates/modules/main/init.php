<?php
//test site part of the main module: scripts/hello.php by convention, feed.xml mapped to it, an old address
$module = Module::Active();
$module->addScriptPath("feed.xml", "class/templates/modules/main/scripts/hello.php");
$module->addRedirect("old.php", "hello");
