<?php


  $all_roles = array(

"ROLE_CONTENT_MENU",
"ROLE_CONFIG_MENU",
"ROLE_ADMIN_USERS_MENU",

);


  foreach($all_roles as $key=>$val) {
	define($val,$val);  
  }


?>