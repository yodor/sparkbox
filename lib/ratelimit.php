<?php
define('FACEBOOK_REQUEST_THROTTLE', 15); // Number of seconds permitted between each hit from facebookexternalhit

if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') === 0) {
    $fbTmpFile = sys_get_temp_dir() . '/facebookexternalhit-sparkbox.txt';
    if ($fh = fopen($fbTmpFile, 'c+')) {
        $lastTime = fread($fh, 100);
        $lastTimeFloat = (float)$lastTime; // Convert string to float
        $microTime = microtime(true);
        // check current microtime with microtime of last access
        if ($microTime - $lastTimeFloat < FACEBOOK_REQUEST_THROTTLE) {
            // bail if requests are coming too quickly with http 429 Too Many Requests
            header($_SERVER["SERVER_PROTOCOL"] . ' 429');
            die;
        } else {
            // write out the microsecond time of last access
            rewind($fh);
            fwrite($fh, (string)$microTime);
        }
        fclose($fh);
    } else {
        header($_SERVER["SERVER_PROTOCOL"] . ' 429');
        die;
    }
}
?>
