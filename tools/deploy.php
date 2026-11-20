<?php
/**
 * Deploy a site of the core layer - a new site in an empty folder, or the SparkBox part of an existing site root
 * again (--update). The store layer has its own: sparkstore/tools/deploy.php (a store site).
 *
 *   php tools/deploy.php --title="My Site" --folder=/home/<user>/sites/mysite --db=<name>:<password> --admin=<email>:<password>
 *   php tools/deploy.php --update --folder=/home/<user>/domains/<site>/public_html [--write] [--remove-links]
 *
 * Run it without arguments for all the options (lib/deploy/SiteDeployment.php).
 */

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit;
}

set_include_path(dirname(__DIR__) . "/lib" . PATH_SEPARATOR . get_include_path());
include_once("deploy/SiteDeployment.php");

$deployment = new SiteDeployment();
$args = array_slice($argv, 1);
if (count($args) < 1) {
    echo $deployment->usage();
    exit(1);
}
try {
    $deployment->parseArguments($args);
}
catch (Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n\n" . $deployment->usage());
    exit(1);
}
exit($deployment->run() ? 0 : 1);
