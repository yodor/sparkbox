<?php
include_once("storage/SparkFile.php");
include_once("storage/WatermarkPosition.php");

class SparkWatermark
{

    protected ?SparkFile $file = null;

    protected bool $enabled = false;

    protected ?GdImage $image = null;

    protected int $margin_x = 10;
    protected int $margin_y = 10;

    protected WatermarkPosition $position = WatermarkPosition::BOTTOM_RIGHT;
    //watermark square size percent over height of image default 1/5 of height
    protected int $size = 5;

    public function  __construct()
    {

        //disabled in config
        if (Spark::GetBoolean(Config::IMAGE_SCALER_WATERMARK_ENABLED)) {

            $watermarkFilename = Spark::Get(Config::IMAGE_SCALER_WATERMARK_FILENAME);
            //no watermark filename in config
            if ($watermarkFilename) {

                $filename = Spark::Get(Config::INSTALL_PATH).DIRECTORY_SEPARATOR.$watermarkFilename;

                $this->file = new SparkFile(realpath($filename));
                Debug::ErrorLog("Watermark using file: ". $this->file->getAbsoluteFilename());
                if ($this->file->exists()) {
                    $this->image = @imagecreatefromstring($this->file->getContents());
                    if ($this->image !== FALSE) {
                        $this->enabled = true;
                        Debug::ErrorLog("Watermark image loaded");
                    }
                    else {
                        Debug::ErrorLog("Unable to read image from this file");
                    }
                }
                else {
                    Debug::ErrorLog("Watermark filename not found");
                }
            }
            else {
                Debug::ErrorLog("Watermark file not set in config");
            }
        }
        else {
            Debug::ErrorLog("Watermark not enabled in config");
        }

        $configPosition = WatermarkPosition::tryFrom(Spark::GetInteger(Config::IMAGE_SCALER_WATERMARK_POSITION));
        if ($configPosition === null) {
            $this->position = WatermarkPosition::BOTTOM_RIGHT;
        }
        else {
            $this->position = $configPosition;
        }

    }

    public function getFile() : ?SparkFile
    {
        return $this->file;
    }
    public function getImage() : ?GdImage
    {
        return $this->image;
    }
    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    public function disable() : void
    {
        $this->enabled = false;
    }

    public function getSize() : int
    {
        return $this->size;
    }

    public function getPosition() : WatermarkPosition
    {
        return $this->position;
    }
    public function getMarginX() : int
    {
        return $this->margin_x;
    }
    public function getMarginY() : int
    {
        return $this->margin_y;
    }

    public function applyTo(GdImage $h_source) : void
    {

        if (!$this->enabled) throw new Exception("Watermark is not enabled");

        $width = imagesx($h_source);
        $height = imagesy($h_source);

        $sx = imagesx($this->image);
        $sy = imagesy($this->image);

        $wtsize = (int)($height / $this->size);

        $margin_x = (int)($wtsize / $this->margin_x);
        $margin_y = (int)($wtsize / $this->margin_y);

        if ($this->position === WatermarkPosition::TOP_LEFT) {
            $dst_x = $margin_x;
            $dst_y = $margin_y;
        } else if ($this->position === WatermarkPosition::TOP_RIGHT) {
            $dst_x = $width - $margin_x - $wtsize;
            $dst_y = $margin_y;
        } else if ($this->position === WatermarkPosition::BOTTOM_LEFT) {
            $dst_x = $margin_x;
            $dst_y = $height - $margin_y - $wtsize;
        } else {

            //if ($this->watermark_position == self::WATERMARK_POSITION_BOTTOM_RIGHT) {
            $dst_x = $width - $margin_x - $wtsize;
            $dst_y = $height - $margin_y - $wtsize;
            //}
        }

        Debug::ErrorLog("Processing watermark on source");
        //imagecopy($h_source, $stamp, imagesx($h_source) - $sx - $marge_right, imagesy($h_source) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
        //imagecopyresized($h_source, $stamp, $this->width - $marge_right - $wtsize, $this->height - $marge_bottom - $wtsize, 0, 0, imagesx($stamp), imagesy($stamp));
        imagecopyresampled($h_source, $this->image, $dst_x, $dst_y,
            0, 0,
            $wtsize, $wtsize,
            $sx, $sy);


    }

}
?>
