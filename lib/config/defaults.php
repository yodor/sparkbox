<?php
define ("DEFAULT_LANGUAGE", "english");
define ("DEFAULT_LANGUAGE_ISO3", "eng");

define ("IMAGE_VALIDATOR_DEFAULT_WIDTH", 1024);
define ("IMAGE_VALIDATOR_DEFAULT_HEIGHT", 768);
define ("IMAGE_VALIDATOR_UPSCALE_ENABLED", false);


define ("DEFAULT_CURRENCY", "USD");


include_once("ini_setup.php");



$umf = ini_get("upload_max_filesize");
KMG($umf);
$pmf = ini_get("post_max_size");
KMG($pmf);
$ml = ini_get("memory_limit");
KMG($ml);

define("UPLOAD_MAX_FILESIZE", $umf);
define("POST_MAX_FILESIZE", $pmf);
define("MEMORY_LIMIT", $ml);


function KMG(&$umf)
{
  if (strpos($umf,"M")!==FALSE) {
	str_replace("M","", $umf);
	$umf = (int)$umf * 1024 * 1024;
  }
  else if (strpos($umf,"K")!==FALSE) {
	str_replace("K","", $umf);
	$umf = (int)$umf * 1024;
  }
}
function file_size($size)
{
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
}
// define("UPLOAD_MAX_FILESIZE", ini_get("upload_max_filesize"));
// define("POST_MAX_FILESIZE", ini_get("post_max_size"));
// define("MEMORY_LIMIT", ini_get("memory_limit"));


define("CONTEXT_USER","context_user");
define("CONTEXT_ADMIN", "context_admin");
?>