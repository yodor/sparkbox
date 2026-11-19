<?php

/**
 * Rate limit of crawlers - off by default. A site turns it on in its config/boot.php:
 *
 *   define("REQUEST_THROTTLE_USERAGENT", "meta-externalagent|facebookexternalhit|SemrushBot");
 *   define("REQUEST_THROTTLE_SECONDS", 30);     //the window (default 30)
 *   define("REQUEST_THROTTLE_CONNCOUNT", 5);    //the requests of a crawler in the window (default 5)
 *
 * A crawler is the part of the user agent the pattern matches ('semrushbot') - all its requests share one counter
 * per installation (a file in the temp folder). An empty user agent counts as the crawler 'emptyua'. Over the limit
 * the answer is 429 with Retry-After; regular browsers never load this file (sparkbox.php checks the pattern first).
 * Mind the search engines: a 429 to Googlebot or bingbot slows the indexing of the site.
 */
class RateLimiter
{
    public static int $ThrottleSeconds = 30;
    public static int $MaxConnections = 5;
    public static string $UserAgentPattern = "";

    //log every counted request (the blocked ones are logged always)
    public static bool $EnableDebug = false;

    private const int PACKET_SIZE = 12;

    /**
     * The check of the request with the REQUEST_THROTTLE_* settings
     * @return void
     */
    public static function Boot() : void
    {
        RateLimiter::$UserAgentPattern = (string)REQUEST_THROTTLE_USERAGENT;
        if (defined("REQUEST_THROTTLE_SECONDS")) RateLimiter::$ThrottleSeconds = (int)REQUEST_THROTTLE_SECONDS;
        if (defined("REQUEST_THROTTLE_CONNCOUNT")) RateLimiter::$MaxConnections = (int)REQUEST_THROTTLE_CONNCOUNT;
        RateLimiter::Check();
    }

    public static function Check() : void
    {
        $botName = RateLimiter::IdentifyBot();
        if ($botName === null) return;

        $installId = hash("xxh3", APP_PATH);
        $file = sys_get_temp_dir() . "/ratelimit-" . preg_replace("/[^a-z0-9_-]/", "", $botName) . "-" . $installId;
        if (RateLimiter::$EnableDebug) error_log("RateLimiter: $botName - $file");

        RateLimiter::Process($file, $botName);
    }

    private static function IdentifyBot() : ?string
    {
        $userAgent = trim((string)($_SERVER["HTTP_USER_AGENT"] ?? ""));
        if ($userAgent === "") return "emptyua";
        if (RateLimiter::$UserAgentPattern === "") return null;

        if (preg_match("/" . RateLimiter::$UserAgentPattern . "/i", $userAgent, $matches)) {
            return strtolower($matches[0]);
        }
        return null;
    }

    private static function Process(string $file, string $botName) : void
    {
        //no counter file - the request goes on (the limit never stops a site)
        $fh = @fopen($file, "c+");
        if (!$fh) return;

        if (!flock($fh, LOCK_EX)) {
            fclose($fh);
            return;
        }

        list($lastTime, $count) = RateLimiter::ReadPacket($fh);
        $now = microtime(true);
        $elapsed = $now - $lastTime;

        if ($count >= RateLimiter::$MaxConnections && $elapsed < RateLimiter::$ThrottleSeconds) {
            flock($fh, LOCK_UN);
            fclose($fh);

            error_log("RateLimiter blocked: $botName ($count requests in " . RateLimiter::$ThrottleSeconds . "s)");
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 429 Too Many Requests", true, 429);
            header("Retry-After: " . max(1, (int)ceil(RateLimiter::$ThrottleSeconds - $elapsed)));
            exit;
        }

        if ($elapsed > RateLimiter::$ThrottleSeconds) {
            RateLimiter::StorePacket($fh, $now, 1);
        }
        else {
            RateLimiter::StorePacket($fh, $lastTime, $count + 1);
        }
        flock($fh, LOCK_UN);
        fclose($fh);
    }

    /**
     * The window start and the request count - 0, 0 for a new or unreadable file
     */
    private static function ReadPacket($fh) : array
    {
        rewind($fh);
        $packet = fread($fh, RateLimiter::PACKET_SIZE);
        if (!is_string($packet) || strlen($packet) < RateLimiter::PACKET_SIZE) return array(0.0, 0);

        $data = @unpack("dt/Lc", $packet);
        if (!is_array($data) || !isset($data["t"], $data["c"])) return array(0.0, 0);

        if (RateLimiter::$EnableDebug) error_log("RateLimiter: window " . $data["t"] . ", count " . $data["c"]);
        return array((float)$data["t"], (int)$data["c"]);
    }

    private static function StorePacket($fh, float $time, int $count) : void
    {
        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, pack("dL", $time, $count));
        fflush($fh);
    }
}
