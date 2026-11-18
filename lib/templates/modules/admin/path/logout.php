<?php
include_once("templates/Module.php");
Module::Active()->getAuthenticator()?->logout();

header("Location: " . Module::PathURL("login"));
exit;
