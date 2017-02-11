<?php


// $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
// $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
// $base_url  = preg_replace("!^${doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
// $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
// $port      = $_SERVER['SERVER_PORT'];
// $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
// $domain    = $_SERVER['SERVER_NAME'];
// $full_url  = "${protocol}://${domain}${disp_port}${base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.

// define("DEBUG_OUTPUT", 0); //enable debugging output using error_log

//uncomment below if deploying in a subfolder of document root
// $install_path = dirname(__FILE__);

//additional folders to add to the include_path if doucument root is different from visible exported web space ie  ~username/ 
//$local_include_path = dirname(__FILE__);

include_once("lib/config/defaults.php");
// include_once("global_beans.php");

//$defines->dump();

?>
