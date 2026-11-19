<?php
//the site root (the front controller) - copy this file as the index.php of the site root and add the rules of
//root.htaccess to the root .htaccess. Every address that is not a file of the site comes here: the modules of
//config/routes.php by their folder (/admin/... → admin), anything else is the main module.
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Route();
