<?php
//the site root (the front controller) - tools/site_deploy.php puts it in the site root with the rules of
//dist/htaccess.dist in the root .htaccess. Every address that is not a file of the site comes here: the modules of
//config/routes.php by their folder (/admin/... → admin), anything else is the main module.
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Route();
