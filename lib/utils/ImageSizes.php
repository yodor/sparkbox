<?php

/**
 * A named image size - one number of pixels (a width or a height: the caller says which, the other side follows
 * the proportions). The core presets: ImageSize; a layer adds its own enum (the store: StoreImageSize).
 */
interface IImageSize
{
    public function pixels() : int;
}

/**
 * The image sizes of the core - the store adds Item and Large (StoreImageSize)
 */
enum ImageSize : int implements IImageSize
{
    //icons, admin list thumbnails, upload previews
    case Tiny = 64;
    //tapes, browsers, small thumbnails
    case Small = 128;
    //galleries, logos, project photos
    case Thumbnail = 256;

    public function pixels() : int
    {
        return $this->value;
    }
}

/**
 * The image sizes the storage answers - one list. Each layer enables its sizes at its boot (the core: ImageSize, the
 * store: StoreImageSize); the site enables, disables or adds sizes in config/settings.php (or config/app.php, after
 * the store). A disabled size stays disabled - a layer that boots later does not enable it again.
 *
 * A requested size that is not enabled is answered with the next larger enabled one (the largest above them all),
 * so the image cache holds only the enabled sizes.
 */
final class ImageSizes
{
    /**
     * @var array<int, bool> pixels => true
     */
    private static array $enabled = array();

    /**
     * @var array<int, bool> pixels => true - disabled for good
     */
    private static array $disabled = array();

    private function __construct()
    {
    }

    /**
     * The pixels of a preset or a number (0 or less: 0 - no limit)
     */
    public static function Pixels(int|IImageSize $size) : int
    {
        $pixels = ($size instanceof IImageSize) ? $size->pixels() : $size;
        return max(0, $pixels);
    }

    public static function Enable(int|IImageSize ...$sizes) : void
    {
        foreach ($sizes as $size) {
            $pixels = ImageSizes::Pixels($size);
            if ($pixels < 1 || isset(ImageSizes::$disabled[$pixels])) continue;
            ImageSizes::$enabled[$pixels] = true;
        }
    }

    public static function Disable(int|IImageSize ...$sizes) : void
    {
        foreach ($sizes as $size) {
            $pixels = ImageSizes::Pixels($size);
            unset(ImageSizes::$enabled[$pixels]);
            ImageSizes::$disabled[$pixels] = true;
        }
    }

    /**
     * The enabled sizes, smallest first
     * @return array<int>
     */
    public static function Enabled() : array
    {
        $sizes = array_keys(ImageSizes::$enabled);
        sort($sizes);
        return $sizes;
    }

    public static function IsEnabled(int $pixels) : bool
    {
        return isset(ImageSizes::$enabled[$pixels]);
    }

    /**
     * The size the storage answers for '$pixels': the same when enabled, else the next larger enabled one, else the
     * largest enabled one; 0 stays 0 (no limit). With no size enabled at all: as asked.
     */
    public static function Allowed(int $pixels) : int
    {
        if ($pixels < 1) return 0;
        $sizes = ImageSizes::Enabled();
        if (count($sizes) < 1 || isset(ImageSizes::$enabled[$pixels])) return $pixels;
        foreach ($sizes as $size) {
            if ($size >= $pixels) return $size;
        }
        return end($sizes);
    }
}
