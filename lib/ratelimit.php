<?php

/**
 * RateLimiter Class
 * Static properties and methods use PascalCase.
 * Local variables and parameters use camelCase.
 * English comments included for professional standard.
 */
class RateLimiter
{
    // Static configuration properties (PascalCase)
    public static int $ThrottleSeconds = 60;
    public static int $MaxConnections = 2;
    public static string $UserAgentPattern = "";
    public static bool $EnableDebug = false;

    private const int PACKET_SIZE = 12;

    public static function Check(): void
    {
        $botName = self::IdentifyBot();

        if ($botName !== null) {
            $installId = hash('xxh3', APP_PATH);
            $tmpFile = sys_get_temp_dir() . "/ratelimit-{$botName}-{$installId}";

            if (self::$EnableDebug) {
                error_log("RateLimiter::Check() - Target: $botName | File: $tmpFile");
            }

            self::Process($tmpFile);
        }
    }

    private static function IdentifyBot(): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? "";

        if (empty(trim($userAgent))) {
            return "EmptyUA";
        }

        if (empty(self::$UserAgentPattern)) return null;

        $pattern = '/' . self::$UserAgentPattern . '/i';
        if (preg_match($pattern, $userAgent, $matches)) {
            return strtolower($matches[0]);
        }

        return null;
    }

    private static function Process(string $tmpFile): void
    {
        $fh = fopen($tmpFile, 'c+');
        if (!$fh) {
            header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Error', true, 500);
            exit;
        }

        if (flock($fh, LOCK_EX)) {
            $data = self::ReadPacket($fh);

            $lastTime  = (float)$data[0];
            $connCount = (int)$data[1];
            $timeNow   = microtime(true);

            if (self::ShouldThrottle($lastTime, $connCount, $timeNow)) {
                flock($fh, LOCK_UN);
                fclose($fh);

                if (self::$EnableDebug) {
                    error_log("RateLimiter Blocked: $tmpFile (Count: $connCount)");
                }

                header($_SERVER["SERVER_PROTOCOL"] . ' 429 Too Many Requests', true, 429);
                exit;
            }

            $timeDiff = $timeNow - $lastTime;

            if ($timeDiff > self::$ThrottleSeconds) {
                $updateData = [$timeNow, 1];
            } else {
                $updateData = [$lastTime, $connCount + 1];
            }

            self::StorePacket($fh, $updateData);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    }

    private static function ShouldThrottle(float $lastTime, int $connCount, float $currentTime): bool
    {
        $timeDiff = $currentTime - $lastTime;
        return ($connCount >= self::$MaxConnections && $timeDiff < self::$ThrottleSeconds);
    }

    private static function ReadPacket($fh): array
    {
        rewind($fh);
        $packet = fread($fh, self::PACKET_SIZE);

        if (empty($packet) || strlen($packet) < self::PACKET_SIZE) {
            if (self::$EnableDebug) {
                error_log("RateLimiter::ReadPacket() - Initializing new data");
            }
            return [0.0, 0];
        }

        // Using named keys 't' for time and 'c' for count to avoid index confusion
        $unpacked = @unpack('dt/Lc', $packet);

        if ($unpacked === false || !isset($unpacked['t'], $unpacked['c'])) {
            if (self::$EnableDebug) {
                error_log("RateLimiter::ReadPacket() - Failed to unpack data");
            }
            return [0.0, 0];
        }

        if (self::$EnableDebug) {
            error_log("RateLimiter::ReadPacket() - Loaded: Time=" . $unpacked['t'] . ", Count=" . $unpacked['c']);
        }

        return [(float)$unpacked['t'], (int)$unpacked['c']];
    }

    private static function StorePacket($fh, array $data): void
    {
        rewind($fh);
        // Pack into binary: d (double), L (unsigned long 32-bit)
        $binaryContent = pack('dL', (float)$data[0], (int)$data[1]);

        ftruncate($fh, 0);
        fwrite($fh, $binaryContent);
        fflush($fh);

        if (self::$EnableDebug) {
            error_log("RateLimiter::StorePacket() - Saved: Time=" . $data[0] . ", Count=" . $data[1]);
        }
    }
}