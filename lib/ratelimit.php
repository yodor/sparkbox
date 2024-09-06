<?php

function storePacket($fh, array $data)
{
    flock($fh, LOCK_EX);
    rewind($fh);
    fwrite($fh, serialize($data));
    fflush($fh);
    flock($fh, LOCK_UN);
}
function readPacket($fh) : array
{
    flock($fh, LOCK_SH);
    rewind($fh);
    $packet = fread($fh, 100);
    @$data = unserialize($packet);
    //var_dump($data);
    if ($data === false) {
        $data = array(0,0);
    }
    flock($fh, LOCK_UN);
    return $data;
}
function rateCheck(string $install_path)
{
    $names = explode("|", REQUEST_THROTTLE_USERAGENT);

    $emptyUserAgent="EmptyUserAgent";
    $userAgent = "";
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = trim($_SERVER['HTTP_USER_AGENT']);
    }

    if (strlen($userAgent)<1) {
        $userAgent = $emptyUserAgent;
        $names[] = $emptyUserAgent;
    }

    foreach ($names as $nameMatch) {

        if (!str_contains($userAgent, $nameMatch)) continue;

        $installID = hash('xxh3', $install_path);

        //temporary file for each installation and userAgent name
        $tmpFile = sys_get_temp_dir() . "/" . $nameMatch . "-" . $installID;

        if ($fh = fopen($tmpFile, 'c+')) {

            $data = readPacket($fh);

            $lastTimeFloat = (float)$data[0]; //last access from this userAgent
            $connCount = (int)$data[1]; //connection count

            $timeNow = microtime(true);
            $timeDiff = $timeNow - $lastTimeFloat;

            $connCount++;

            //number of connection exceeded for the amount of time given
            if ($connCount > REQUEST_THROTTLE_CONNCOUNT && $timeDiff < REQUEST_THROTTLE_SECONDS) {
                // bail with http 429 Too Many Requests
                header($_SERVER["SERVER_PROTOCOL"] . ' 429');
//                    $data[0] = (float)$lastTimeFloat;
//                    $data[1] = (int)$connCount;
//                    storePacket($fh, $data);
                fclose($fh);
                exit;
            }
            if ($timeDiff > REQUEST_THROTTLE_SECONDS) {
                //reset connection count
                $data[0] = (float)$timeNow;
                $data[1] = (int)1;
                storePacket($fh, $data);
                fclose($fh);
                return;
            }

            //allow this request, store the current connection count
            $data[0] = (float)$lastTimeFloat;
            $data[1] = (int)$connCount;
            storePacket($fh, $data);
            fclose($fh);
        }
        else {
            header($_SERVER["SERVER_PROTOCOL"] . ' 430');
            exit;
        }
    }


}

rateCheck($install_path);

?>
