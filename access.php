<?php
include_once("session.php");

echo "Install Path: " . INSTALL_PATH;
echo "<BR>";
echo "Lib Path: " . LIB_PATH;
echo "<BR>";
echo "Site Root URL: " . SITE_ROOT;
echo "<BR>";
echo "Lib Root URL: " . LIB_ROOT;
echo "<BR>";
echo "Cache Root: " . CACHE_ROOT;
echo "<BR>";
echo ini_get("include_path");
echo "<BR>";
$defines->dump();

?>
