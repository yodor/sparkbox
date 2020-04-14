<?php
// error_reporting(E_ALL & ~E_WARNING);
error_reporting(E_ALL);
ini_set("display_errors","1");
ini_set("display_startup_errors","0");
ini_set("auto_detect_line_endings","1");
ini_set("session.cookie_lifetime", "0");
ini_set("session.use_only_cookies", "0");

ini_set("zlib.output_compression", "0");

@ini_set("mbstring.internal_encoding", "UTF-8");

// ini_set("session.cookie_lifetime","7200");
// ini_set("session.gc_maxlifetime","36000");

//important for storage cache to work is the timezone matching across the lamp stack, apache uses the system timezone
//mysql driver uses data.timezone to set the mysql timezone
ini_set("date.timezone","Europe/Sofia");


//defined in .htaccess in the docroot
// <IfModule mod_php5.c>
// php_value upload_max_filesize 14000000
// php_value post_max_size 14000000
// php_value memory_limit 128000000
// </IfModule>
//httpd.conf should contain for the docroot for .htaccess to work
//<Directory "/srv/http/acw">
//  AllowOverride Options
//</Directory>

$_REQUEST = array_merge($_GET, $_POST);
?>
