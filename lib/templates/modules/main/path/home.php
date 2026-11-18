<?php
//the site home of a core site without its own (a site sets a script path 'home' or its own path file)
$config = Plain::Configure(Spark::Get(Config::SITE_TITLE), "");
$config->requireAuth = false;
