<?php
//main module entry - copy this file as the index.php of the site root and add the rules of root.htaccess to the
//root .htaccess: the addresses that are not files or folders of the site go to the module
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Run("main");
