<?php
$cdir = dirname(__FILE__);
$realpath = realpath ( $cdir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR );
include_once($realpath."/session.php");

include_once("lib/buttons/StyledButton.php");
StyledButton::setDefaultClass("admin_button");
?>