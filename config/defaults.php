<?php
//init defaults/globals here

// define ("CONTEXT_ADMIN","admin");
// define ("CONTEXT_MEDIA_CENTRE","mcentre");
// define ("CONTEXT_CUPDATES","cupdates");
// define ("CONTEXT_KB","kb");
//   
// 
// define ("SUBSCRIBE_DVD","SUBSCRIBE_DVD");
// define ("SUBSCRIBE_GENERAL","SUBSCRIBE_GENERAL");
// 
// 
// 
define ("ADMIN_ROOT", SITE_ROOT."admin/");
// define ("ACCOUNT_ROOT", SITE_ROOT."account/");

include_once("admin_roles.php");

// include_once("class/buttons/SiteButton.php");
// StyledButton::setDefaultButton(new SiteButton());
// 
define ("IMAGE_UPLOAD_DEFAULT_WIDTH", IMAGE_VALIDATOR_DEFAULT_WIDTH);
define ("IMAGE_UPLOAD_DEFAULT_HEIGHT", IMAGE_VALIDATOR_DEFAULT_HEIGHT);
define ("IMAGE_UPLOAD_UPSCALE_ENABLED", IMAGE_VALIDATOR_UPSCALE_ENABLED);

?>
