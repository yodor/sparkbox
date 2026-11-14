<?php
//admin module entry - link this folder into the site document root (any folder name, e.g. admin2/ or admin/)
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Run("admin");
