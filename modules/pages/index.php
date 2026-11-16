<?php
//pages module entry (dynamic pages) - link this folder into the site document root as 'pages'
include_once("templates/Template.php");
include_once("templates/Module.php");

Module::Run("pages");
