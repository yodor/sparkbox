<?php
$userAgent = $_SERVER['HTTP_USER_AGENT'];
if (!empty($userAgent)) {
    $names = explode("|", REQUEST_THROTTLE_USERAGENT);

    foreach ($names as $nameMatch) {

        if (!str_contains($userAgent, $nameMatch)) continue;

        $installID = hash('xxh3',   $install_path);

        $tmpFile = sys_get_temp_dir() . "/". $nameMatch."-".$installID;

        if ($fh = fopen($tmpFile, 'c+')) {

            $microTime = microtime(true);

            flock($fh, LOCK_SH);
            $lastTime = fread($fh, 15);
            flock($fh, LOCK_UN);

            $lastTimeFloat = (float)$lastTime; // Convert string to float

            $timeDiff = $microTime - $lastTimeFloat;

            // check current microtime with microtime of last access
            if ($timeDiff < REQUEST_THROTTLE_SECONDS) {
                // bail if requests are coming too quickly with http 429 Too Many Requests
                header($_SERVER["SERVER_PROTOCOL"] . ' 429');
                exit;
            } else {
                // write out the microsecond time of last access
                flock($fh, LOCK_EX);
                rewind($fh);
                fwrite($fh, (string)$microTime);
                fflush($fh);
                flock($fh, LOCK_UN);
            }
            fclose($fh);

        }
    }

}
?>
