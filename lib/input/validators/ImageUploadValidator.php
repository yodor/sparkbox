<?php
include_once("input/validators/UploadDataValidator.php");
include_once("storage/ImageStorageObject.php");

class ImageUploadValidator extends UploadDataValidator
{

    private $resize_width = -1;
    private $resize_height = -1;
    private $resize_enabled = TRUE;

    public function __construct()
    {
        parent::__construct();

        $accept_mimes = array("image/webp", "image/jpeg", "image/jpg", "image/png", "image/gif", "application/octet-stream");
        $this->setAcceptMimes($accept_mimes);

        if (IMAGE_UPLOAD_UPSCALE || IMAGE_UPLOAD_DOWNSCALE) {
            $this->resize_enabled = TRUE;
            $this->resize_width = IMAGE_UPLOAD_DEFAULT_WIDTH;
            $this->resize_height = IMAGE_UPLOAD_DEFAULT_HEIGHT;
        }
        else {
            $this->resize_enabled = FALSE;
        }
    }

    public function setResizedSize($width, $height)
    {
        $this->resize_width = $width;
        $this->resize_height = $height;
        $this->resize_enabled = TRUE;
    }

    /**
     * @param bool $mode
     */
    public function setResizeEnabled(bool $mode)
    {
        $this->resize_enabled = $mode;
    }

    public function validate(DataInput $input)
    {
        parent::validate($input);

        debug("Input: " . $input->getName());

        //field->getValue() contains FileStorageObject as uploaded from user
        $image_storage = new ImageStorageObject($input->getValue());

        $this->processObject($image_storage);

        //assign ImageStorageObject to field after processing
        $input->setValue($image_storage);

    }

    /**
     * Clamp image size if config options are set
     * Handle resize of uploaded image before storing to DB
     * @param ImageStorageObject $image_storage
     * @throws Exception
     */
    public function processObject(StorageObject $image_storage)
    {

        debug("UID: " . $image_storage->getUID());

        debug("Image dimension: [" . $image_storage->getWidth() . " x " . $image_storage->getHeight() . "]");
        debug("MIME: " . $image_storage->getMIME());
        debug("Data size: " . $image_storage->getLength());

        //should be disabled during ajax upload and before submit of actual form. original uploaded image is stored in session
        if (!$this->resize_enabled) {
            debug("Resizing is disabled for this validator");
            return;
        }

        $scale = 1;
        $dst_width = $this->resize_width;
        $dst_height = $this->resize_height;

        if ($dst_width < 1 && $dst_height < 1) throw new Exception("Resize is enabled but resize width or height is not set");

        if ($dst_width > 0 && $dst_height > 0) {

            debug("Mode 'Exact Size' - new dimension is [ $dst_width x $dst_height ]");

        }
        else if ($dst_width > 0) {

            $ratio = (float)$image_storage->getWidth() / $dst_width;
            $dst_height = $image_storage->getHeight() / $ratio;
            debug("Mode 'Autofit Width' - new dimension is [ $dst_width x $dst_height ] - ratio: $ratio");

        }
        else if ($dst_height > 0) {

            $ratio = (float)$image_storage->getHeight() / $dst_height;
            $dst_width = $image_storage->getWidth() / $ratio;
            debug("Mode 'Autofit Height' - new dimension is [ $dst_width x $dst_height ] - ratio: $ratio");
        }

        $scale = min($dst_width / $image_storage->getWidth(), $dst_height / $image_storage->getHeight());

        if ($scale != 1) {

            if ($scale > 1) {
                if (IMAGE_UPLOAD_UPSCALE) {
                    debug("IMAGE_UPLOAD_UPSCALE is true");
                }
                else {
                    debug("IMAGE_UPLOAD_UPSCALE is false - using scale: 1");
                    //force 1:1 scale
                    $scale = 1;
                }
            }
            else if ($scale < 1) {
                if (IMAGE_UPLOAD_DOWNSCALE) {
                    debug("IMAGE_UPLOAD_DOWNSCALE is true");
                }
                else {
                    debug("IMAGE_UPLOAD_DOWNSCALE is false - using scale: 1");
                    //force 1:1 scale
                    $scale = 1;
                }
            }

        }

        debug("Scale is: " . $scale);

        if ($scale == 1) {
            debug("Scale is 1 - finishing without resize");

            return;
        }

        $n_width = $image_storage->getWidth() * $scale;
        $n_height = $image_storage->getHeight() * $scale;

        if ($n_width < 1) $n_width = 1;
        if ($n_height < 1) $n_height = 1;

        debug("Creating new image: [ $n_width x $n_height ] | Memory usage before scaling: " . memory_get_usage(TRUE));

        $source = FALSE;

        if ($image_storage->haveData()) {
            $source = @imagecreatefromstring($image_storage->getData());
        }
        else {
            $source = $image_storage->imageFromTemp();
        }

        if (!$source) throw new Exception("Unable to create image resource from this input data");

        $n_width = (int)$n_width;
        $n_height = (int)$n_height;

        $scaled_source = imagecreatetruecolor($n_width, $n_height);
        imagealphablending($scaled_source, FALSE);

        // Resize
        imagecopyresampled($scaled_source, $source, 0, 0, 0, 0, $n_width, $n_height, $image_storage->getWidth(), $image_storage->getHeight());
        @imagedestroy($source);

        debug("Saving new image to output buffer ...");

        ob_start();

        if (strcasecmp($image_storage->getMIME(), ImageScaler::TYPE_PNG) == 0) {

            $image_storage->setMIME(ImageScaler::TYPE_PNG);

            debug("Using PNG output");

            imagesavealpha($scaled_source, TRUE);

            imagepng($scaled_source);

        }
        else {

            $image_storage->setMIME(ImageScaler::TYPE_JPEG);

            debug("Using JPEG output");

            imagejpeg($scaled_source, NULL, 95);

        }

        debug("Output buffer size: " . ob_get_length());

        $image_storage->setData(ob_get_contents());

        ob_end_clean();

        @imagedestroy($scaled_source);

    }

}

?>