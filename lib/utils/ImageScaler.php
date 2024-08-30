<?php
include_once("storage/DataBuffer.php");

class ImageScaler
{

    const WATERMARK_POSITION_TOP_LEFT = 1;
    const WATERMARK_POSITION_TOP_RIGHT = 2;
    const WATERMARK_POSITION_BOTTOM_LEFT = 3;
    const WATERMARK_POSITION_BOTTOM_RIGHT = 4;

    const MODE_FULL = 0;
    const MODE_CROP = 1;
    const MODE_THUMB = 2;

    const TYPE_JPEG = "image/jpeg";
    const TYPE_PNG = "image/png";
    const TYPE_WEBP = "image/webp";

    protected $supported_mimes = array(ImageScaler::TYPE_JPEG, ImageScaler::TYPE_PNG, ImageScaler::TYPE_WEBP);

    /**
     * @var string Output image format
     */
    protected $output_format = ImageScaler::TYPE_WEBP;
    protected $output_quality = 60;

    protected $mode = ImageScaler::MODE_FULL;

    protected $width = -1;
    protected $height = -1;

    public $grayFilter = FALSE;

    public $watermark_required = FALSE;

    protected $watermark_enabled = FALSE;
    protected $watermark_data = FALSE;

    protected $watermark_margin_x = 10;
    protected $watermark_margin_y = 10;

    protected $watermark_position = ImageScaler::WATERMARK_POSITION_BOTTOM_RIGHT;
    //watermark square size percent over height of image default 1/5 of height
    protected $watermark_size = 5;


    public $upscale_enabled = FALSE;
    public $downscale_enabled = FALSE;


    //resulting image width and height
    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;

        if ($this->width > 0 || $this->height > 0) {
            $this->mode = ImageScaler::MODE_CROP;

            if ($this->width == $this->height) {
                $this->mode = ImageScaler::MODE_THUMB;
            }
        }
        $this->output_format = IMAGE_SCALER_OUTPUT_FORMAT;
        $this->output_quality = IMAGE_SCALER_OUTPUT_QUALITY;

        if (defined("IMAGE_SCALER_WATERMARK_POSITION")) {
            $this->watermark_position = (int)IMAGE_SCALER_WATERMARK_POSITION;
        }

        //disabled in config
        if (defined("IMAGE_SCALER_WATERMARK_ENABLED")) {
            if (IMAGE_SCALER_WATERMARK_ENABLED) {
                //no watermark filename in config
                if (defined("IMAGE_SCALER_WATERMARK_FILENAME")) {
                    $this->watermark_data = @imagecreatefromstring(file_get_contents(IMAGE_SCALER_WATERMARK_FILENAME, true));
                    if ($this->watermark_data !== FALSE) {
                        $this->watermark_enabled = true;
                    }
                }
            }
        }

    }

    public function __destruct()
    {
        if ($this->watermark_data !== FALSE) {
            @imagedestroy($this->watermark_data);
        }
    }

    public function getWatermarkPosition(): int
    {
        return $this->watermark_position;
    }

    public function isWatermarkEnabled(): bool
    {
        return $this->watermark_enabled;
    }

    public function setOutputQuality(int $output_quality)
    {
        $this->output_quality = $output_quality;
    }

    public function getOutputQuality(): int
    {
        return $this->output_quality;
    }

    /**
     * @param string $mime Set the output format from mime type string
     */
    public function setOutputFormat(string $mime)
    {
        if (!in_array($mime, $this->supported_mimes)) throw new RuntimeException("Unsupported output format type");

        $this->output_format = $mime;
    }

    /**
     * @return string Return the output format mime type string
     */
    public function getOutputFormat(): string
    {
        return $this->output_format;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function process(DataBuffer $buffer)
    {

        if ($this->mode == ImageScaler::MODE_CROP) {
            debug("Using CROP mode");
            $this->processCrop($buffer);
        } else if ($this->mode == ImageScaler::MODE_THUMB) {
            debug("Using THUMB mode");
            $this->processThumb($buffer);
        } else {
            //as is
            debug("Using as-is mode");
            if ($this->watermark_required && $this->watermark_enabled) {
                $h_source = @imagecreatefromstring($buffer->getData());
                if ($h_source === FALSE) {
                    throw new Exception("Image can not be created from this input data");
                }
                $this->setImageData($buffer, $h_source);
                imagedestroy($h_source);
            }
        }
    }

    protected function processCrop(DataBuffer $buffer) : void
    {
        $h_source = @imagecreatefromstring($buffer->getData());
        if ($h_source === FALSE) {
            throw new Exception("Image can not be created from this input data");
        }

        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);

        //1264x720
        //449x256
        $ratio = 0;
        if ($this->width > 0 && $this->height > 0) {

            $pix_req = $this->width * $this->height;
            $pix_img = $image_width * $image_height;

            //upscale
            if ($pix_req > $pix_img) {

            } //downscale
            else {

            }

        } else {
            if ($this->height > 0) {
                //scale to height , width auto
                $ratio = $image_height / $this->height;
                $this->width = (int)($image_width / $ratio);

                debug("Using fit to height");
            } else if ($this->width > 0) {
                $ratio = $image_width / $this->width;
                $this->height = (int)($image_height / $ratio);

                debug("Using fit to width");
            }
        }

        debug("Output size: [$this->width, $this->height]");
        $h_crop = $this->createImage($this->width, $this->height);

        // Resize
        imagecopyresampled($h_crop, $h_source, 0, 0, 0, 0, $this->width, $this->height, $image_width, $image_height);
        imagedestroy($h_source);

        $this->setImageData($buffer, $h_crop);

        imagedestroy($h_crop);

    }

    protected function processThumb(DataBuffer $buffer) : void
    {

        $h_source = @imagecreatefromstring($buffer->getData());
        if ($h_source === FALSE) {
            throw new Exception("Image can not be created from this input data");
        }

        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);
        $image_size = min($image_width, $image_height);

        $h_crop = imagecrop($h_source, ['x' => ($image_width - $image_size) / 2,
            'y' => ($image_height - $image_size) / 2, 'width' => $image_size,
            'height' => $image_size]);
        imagedestroy($h_source);

        $h_thumbnail = $this->createImage($this->width, $this->width);

        imagecopyresampled($h_thumbnail, $h_crop, 0, 0, 0, 0, $this->width, $this->width, $image_size, $image_size);
        imagedestroy($h_crop);

        $this->setImageData($buffer, $h_thumbnail);

        imagedestroy($h_thumbnail);
    }

    protected function processWatermark(GdImage $h_source) : void
    {


        $width = imagesx($h_source);
        $height = imagesy($h_source);

        $sx = imagesx($this->watermark_data);
        $sy = imagesy($this->watermark_data);

        $wtsize = (int)($height / $this->watermark_size);

        $margin_x = (int)($wtsize / $this->watermark_margin_x);
        $margin_y = (int)($wtsize / $this->watermark_margin_y);

        if ($this->watermark_position == self::WATERMARK_POSITION_TOP_LEFT) {
            $dst_x = $margin_x;
            $dst_y = $margin_y;
        } else if ($this->watermark_position == self::WATERMARK_POSITION_TOP_RIGHT) {
            $dst_x = $width - $margin_x - $wtsize;
            $dst_y = $margin_y;
        } else if ($this->watermark_position == self::WATERMARK_POSITION_BOTTOM_LEFT) {
            $dst_x = $margin_x;
            $dst_y = $height - $margin_y - $wtsize;
        } else {

            //if ($this->watermark_position == self::WATERMARK_POSITION_BOTTOM_RIGHT) {
            $dst_x = $width - $margin_x - $wtsize;
            $dst_y = $height - $margin_y - $wtsize;
            //}
        }

        debug("Processing watermark on source");
        //imagecopy($h_source, $stamp, imagesx($h_source) - $sx - $marge_right, imagesy($h_source) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
        //imagecopyresized($h_source, $stamp, $this->width - $marge_right - $wtsize, $this->height - $marge_bottom - $wtsize, 0, 0, imagesx($stamp), imagesy($stamp));
        imagecopyresampled($h_source, $this->watermark_data, $dst_x, $dst_y,
            0, 0,
            $wtsize, $wtsize,
            $sx, $sy);


    }

    protected function setImageData(DataBuffer $buffer, GdImage $h_source): void
    {
        debug("Set image data ...");
        if ($this->grayFilter) {
            imagefilter($h_source, IMG_FILTER_GRAYSCALE);
        }

        if ($this->watermark_required && $this->watermark_enabled) {
            $this->processWatermark($h_source);
        }

        ob_start(NULL, 0);
        if (strcmp($this->output_format, ImageScaler::TYPE_PNG) == 0) {
            imagesavealpha($h_source, TRUE);
            imagepng($h_source);
        } else if (strcmp($this->output_format, ImageScaler::TYPE_JPEG) == 0) {
            imagejpeg($h_source, NULL, $this->output_quality);
        } else {
            imagewebp($h_source, NULL, $this->output_quality);
        }

        $buffer->setData(ob_get_contents());

        ob_end_clean();
    }

    protected function createImage(int $width, int $height) : GdImage
    {
        $h_thumbnail = imagecreatetruecolor($width, $height);
        imageantialias($h_thumbnail, TRUE);
        imagealphablending($h_thumbnail, TRUE);
        return $h_thumbnail;
    }

}
