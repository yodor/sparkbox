<?php
$cdir = dirname(__FILE__);
$realpath = realpath ( $cdir.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR );
include_once($realpath."/session.php");

include_once("lib/buttons/StyledButton.php");
StyledButton::setDefaultClass("admin_button");


  $all_roles = array(

"ROLE_CONTENT_MENU",
"ROLE_CONFIG_MENU",
"ROLE_ADMIN_USERS_MENU",

);


  foreach($all_roles as $key=>$val) {
	define($val,$val);  
  }
  
?>