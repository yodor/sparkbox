<?php

class ImageScaler
{

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

    /**
     * @var string Input image mime type
     */
    protected $mime = ImageScaler::TYPE_PNG;

    protected $mode = ImageScaler::MODE_FULL;

    protected $width = -1;
    protected $height = -1;

    public $grayFilter = FALSE;

    public $upscale_enabled = FALSE;
    public $downscale_enabled = FALSE;

    protected $data = NULL;
    protected $dataSize = 0;

    protected $output_quality = 60;

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
    }

    public function setOutputQuality(int $output_quality)
    {
        $this->output_quality = $output_quality;
    }
    public function getOutputQuality() : int {
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
    public function getOutputFormat() : string
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

    public function process(string $data, int $length, string $mime)
    {

        $this->mime = $mime;
        $this->data = $data;
        $this->dataSize = $length;

        if ($this->mode == ImageScaler::MODE_CROP) {
            $this->processCrop();
        }
        else if ($this->mode == ImageScaler::MODE_THUMB) {
            $this->processThumb();
        }
        else {
            //as is
        }
    }

    protected function processCrop()
    {
        $h_source = @imagecreatefromstring($this->data);
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

            }
            //downscale
            else {

            }

        }
        else {
            if ($this->height > 0) {
                //scale to height , width auto
                $ratio = $image_height / $this->height;
                $this->width = (int)($image_width / $ratio);

                debug("Using fit to height");
            }
            else if ($this->width > 0) {
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

        $this->setImageData($h_crop);

        imagedestroy($h_crop);

    }

    protected function processThumb()
    {

        $h_source = @imagecreatefromstring($this->data);
        if ($h_source === FALSE) {
            throw new Exception("Image can not be created from this input data");
        }

        $image_width = imagesx($h_source);
        $image_height = imagesy($h_source);
        $image_size = min($image_width, $image_height);

        $h_crop = imagecrop($h_source, ['x'      => ($image_width - $image_size) / 2,
                                        'y'      => ($image_height - $image_size) / 2, 'width' => $image_size,
                                        'height' => $image_size]);
        imagedestroy($h_source);

        $h_thumbnail = $this->createImage($this->width, $this->width);

        imagecopyresampled($h_thumbnail, $h_crop, 0, 0, 0, 0, $this->width, $this->width, $image_size, $image_size);
        imagedestroy($h_crop);

        $this->setImageData($h_thumbnail);

        imagedestroy($h_thumbnail);
    }

    protected function setImageData($h_source)
    {
        if ($this->grayFilter) {
            imagefilter($h_source, IMG_FILTER_GRAYSCALE);
        }

        ob_start(NULL, 0);
        if (strcmp($this->output_format, ImageScaler::TYPE_PNG) == 0) {
            imagesavealpha($h_source, TRUE);
            imagepng($h_source);
        }
        else if (strcmp($this->output_format, ImageScaler::TYPE_JPEG) == 0){
            imagejpeg($h_source, NULL, $this->output_quality);
        }
        else {
            imagewebp($h_source, NULL, $this->output_quality);
        }
        $this->data = ob_get_contents();
        $this->dataSize = ob_get_length();

        ob_end_clean();
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getDataSize(): int
    {
        return $this->dataSize;
    }

    protected function createImage(int $width, int $height)
    {
        $h_thumbnail = imagecreatetruecolor($width, $height);
        imageantialias($h_thumbnail, TRUE);
        imagealphablending($h_thumbnail, TRUE);
        return $h_thumbnail;
    }
}