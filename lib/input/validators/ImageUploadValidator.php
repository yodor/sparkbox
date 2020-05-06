<?php
include_once("input/validators/UploadDataValidator.php");
include_once("storage/ImageStorageObject.php");
include_once("utils/ImageResizer.php");


class ImageUploadValidator extends UploadDataValidator
{

    private $resize_width = 0;
    private $resize_height = 0;
    private $resize_enabled = true;

    public function __construct()
    {
        parent::__construct();

        $accept_mimes = array("image/jpeg", "image/jpg", "image/png", "image/gif", "application/octet-stream");
        $this->setAcceptMimes($accept_mimes);

        if (IMAGE_UPLOAD_UPSCALE || IMAGE_UPLOAD_DOWNSCALE) {
            $this->resize_enabled = true;
            $this->resize_width = IMAGE_UPLOAD_DEFAULT_WIDTH;
            $this->resize_height = IMAGE_UPLOAD_DEFAULT_HEIGHT;
        }
    }

    public function setResizedSize($width, $height)
    {
        $this->resize_width = $width;
        $this->resize_height = $height;
        $this->resize_enabled = true;
    }

    /**
     * @param bool $mode
     */
    public function setResizeEnabled(bool $mode)
    {
        $this->resize_enabled = $mode;
    }

    protected function processUploadData(DataInput $field)
    {

        debug("ImageUploadValidator::processUploadData() for field[" . $field->getName() . "]");

        //field->getValue() contains FileStorageObject as uploaded from user
        $image_storage = new ImageStorageObject($field->getValue());

        $this->process($image_storage);

        //assign ImageStorageObject to field after processing
        $field->setValue($image_storage);

    }

    public function process(ImageStorageObject $image_storage)
    {


        debug("------------- ImageUploadValidator::processImage() UID: " . $image_storage->getUID());

        debug("Uploaded Image Size:(" . $image_storage->getWidth() . "," . $image_storage->getHeight() . ")");
        debug("MIME: " . $image_storage->getMIME());
        debug("Length: " . $image_storage->getLength());


        //should be disabled during ajax upload and before submit of actual form. original uploaded image is stored in session
        if (!$this->resize_enabled) {
            debug("Resizing is disabled for this validator");
            debug("----------------------------------------------------------------------");
            return;
        }


        $scale = 1;
        $dst_width = $this->resize_width;
        $dst_height = $this->resize_height;

        if ($dst_width == 0 && $dst_height == 0) throw new Exception("Resize is enabled but resize width or height is not set");

        if ($dst_width > 0 && $dst_height > 0) {

            debug("Exact resize dimension requested: ($dst_width, $dst_height)");

        }
        else if ($dst_width > 0) {

            $ratio = (float)$image_storage->getWidth() / $dst_width;
            $dst_height = $image_storage->getHeight() / $ratio;
            debug("Autofit Width Result: Size ($dst_width, $dst_height) - Ratio: $ratio");

        }
        else if ($dst_height > 0) {

            $ratio = (float)$image_storage->getHeight() / $dst_height;
            $dst_width = $image_storage->getWidth() / $ratio;
            debug("Autofit Height Result: Size ($dst_width, $dst_height) - Ratio: $ratio");

        }

        $scale = min($dst_width / $image_storage->getWidth(), $dst_height / $image_storage->getHeight());
        debug("Scale: $scale");

        if ($scale != 1) {

            if ($scale > 1) {
                if (IMAGE_UPLOAD_UPSCALE) {
                    debug("IMAGE_UPLOAD_UPSCALE is true. Upscaling is enabled.");
                }
                else {
                    debug("IMAGE_UPLOAD_UPSCALE is false. Upscaling is disabled.");
                    //force 1:1 scale
                    $scale = 1;
                }
            }
            else if ($scale < 1) {
                if (IMAGE_UPLOAD_DOWNSCALE) {
                    debug("IMAGE_UPLOAD_DOWNSCALE is true. Downscaling is enabled.");
                }
                else {
                    debug("IMAGE_UPLOAD_DOWNSCALE is false. Downscaling is disabled.");
                    //force 1:1 scale
                    $scale = 1;
                }
            }

        }

        debug("Scale: " . $scale);

        if ($scale == 1) {
            debug("Scale is unity. Finishing processImage without resize.");
            debug("----------------------------------------------------------------------");
            return;
        }

        $n_width = $image_storage->getWidth() * $scale;
        $n_height = $image_storage->getHeight() * $scale;

        if ($n_width < 1) $n_width = 1;
        if ($n_height < 1) $n_height = 1;


        debug("Creating scaled image ($n_width, $n_height) | Memory usage before scaling: " . memory_get_usage(true));


        $source = false;

        if ($image_storage->haveData()) {
            $source = imagecreatefromstring($image_storage->getData());
        }
        else {
            $source = $image_storage->imageFromTemp();
        }

        if (!is_resource($source)) throw new Exception("ImageUploadValidator::processImage() can not create image resource from data");


        $scaled_source = imagecreatetruecolor($n_width, $n_height);
        imagealphablending($scaled_source, false);

        // Resize
        imagecopyresampled($scaled_source, $source, 0, 0, 0, 0, $n_width, $n_height, $image_storage->getWidth(), $image_storage->getHeight());
        @imagedestroy($source);

        debug("Processing image data to output buffer ...");

        ob_start();

        if (strcmp(strtolower($image_storage->getMIME()), ImageResizer::TYPE_PNG) === 0) {

            $image_storage->setMIME(ImageResizer::TYPE_PNG);

            debug("Output Format is PNG");

            imagesavealpha($scaled_source, true);

            imagepng($scaled_source);

        }
        else {

            $image_storage->setMIME(ImageResizer::TYPE_JPEG);

            debug("Output Format is JPEG");

            imagejpeg($scaled_source, NULL, 95);

        }

        // pass output to image_storage
        debug("Setting output buffer result as image data ...");
        $image_storage->setData(ob_get_contents());

        ob_end_clean();

        @imagedestroy($scaled_source);


        debug("----------------------------------------------------------------------");
    }

}

?>
