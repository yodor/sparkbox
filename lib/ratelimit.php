<?php

class RateLimiter
{
    // Static properties in PascalCase
    public static int $ThrottleSeconds = 60;
    public static int $MaxConnections = 1;
    public static string $UserAgentPattern = "";

    private const int PACKET_SIZE = 12;

    public static function Check(): void
    {
        // Local variable in camelCase
        $botName = self::IdentifyBot();

        if ($botName !== null) {
            $installId = hash('xxh3', APP_PATH);
            $tmpFile = sys_get_temp_dir() . "/ratelimit-{$botName}-{$installId}";

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
            header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Error');
            exit;
        }

        if (flock($fh, LOCK_EX)) {
            // Parameters and local variables in camelCase
            $data = self::ReadPacket($fh);
            $timeNow = microtime(true);

            if (self::ShouldThrottle($data[0], $data[1], $timeNow)) {
                header($_SERVER["SERVER_PROTOCOL"] . ' 429 Too Many Requests');
                flock($fh, LOCK_UN);
                fclose($fh);
                exit;
            }

            $timeDiff = $timeNow - $data[0];
            if ($timeDiff > self::$ThrottleSeconds) {
                $data = [$timeNow, 1];
            } else {
                $data[1]++;
            }

            self::StorePacket($fh, $data);
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
        if (strlen($packet) < self::PACKET_SIZE) return [0.0, 0];

        $unpacked = unpack('d1/L2', $packet);
        return [$unpacked[1], $unpacked[2]];
    }

    private static function StorePacket($fh, array $data): void
    {
        rewind($fh);
        fwrite($fh, pack('dL', (float)$data[0], (int)$data[1]));
        fflush($fh);
    }
}