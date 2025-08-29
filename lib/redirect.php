<?php

$host = mb_strtolower($_SERVER['HTTP_HOST']);

$location = "";
$protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ? "https" : "http";

$doRedirect = false;

if (isset($GLOBALS["NO_SUBDOMAIN_REDIRECT"])) {
}
else {
    if (!isset($GLOBALS["REDIRECT_SUBDOMAINS"])) {
        $GLOBALS["REDIRECT_SUBDOMAINS"] = "mail;www";
    }
    $redirect_subdomains = explode(";", $GLOBALS["REDIRECT_SUBDOMAINS"]);

    if (count($redirect_subdomains) > 0) {
        foreach ($redirect_subdomains as $subdomain) {
            if ($subdomain) {
                $subdomain = $subdomain . ".";
                if (str_starts_with($host, $subdomain)) {
                    $host = str_replace($subdomain, "", $host);
                    $doRedirect = true;
                    break;
                }
            }
        }
    }
}

if (isset($GLOBALS["FORCE_HTTPS"])) {
    //force https
    if (strcmp($protocol,"http")===0) {
        $protocol = "https";
        $doRedirect = true;
    }
}

$location = $protocol . "://" . $host . $_SERVER['REQUEST_URI'];

if ($doRedirect) {
    header('HTTP/2 301 Moved Permanently');
    header('Location: ' . $location);
    exit;
}
?>