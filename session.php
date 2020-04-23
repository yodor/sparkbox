<?php
define("DEBUG_OUTPUT", 1); //enable debugging output using error_log

//uncomment below if deploying in a subfolder of document root
$install_path = dirname(__FILE__);

//additional folders to add to the include_path if doucument root is different from visible exported web space ie  ~username/ 
//$local_include_path = dirname(__FILE__);

include_once("lib/config/defaults.php");
// include_once("global_beans.php");

// echo "Install Path: ".INSTALL_PATH;
// echo "<BR>";
// echo "Lib Path: ".LIB_PATH;
// echo "<BR>";
// echo "Site Root URL: ".SITE_ROOT;
// echo "<BR>";
// echo "Lib Root URL: ".LIB_ROOT;
// echo "<BR>";
// echo "Cache Root: ".CACHE_ROOT;
// echo "<BR>";
// 
// $defines->dump();

?>
