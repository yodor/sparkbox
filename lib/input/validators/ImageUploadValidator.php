<?php
include_once("input/validators/UploadDataValidator.php");
include_once("storage/ImageStorageObject.php");

class ImageUploadValidator extends UploadDataValidator
{

    protected int $resize_width = -1;
    protected int $resize_height = -1;
    protected bool $resize_enabled = TRUE;

    public function __construct()
    {
        parent::__construct();

        $accept_mimes = array("image/webp", "image/jpeg", "image/jpg", "image/png", "image/gif", "application/octet-stream");
        $this->setAcceptMimes($accept_mimes);

        if (IMAGE_UPLOAD_UPSCALE || IMAGE_UPLOAD_DOWNSCALE) {
            $this->setResizedSize(IMAGE_UPLOAD_DEFAULT_WIDTH, IMAGE_UPLOAD_DEFAULT_HEIGHT);
        }
        else {
            $this->setResizeEnabled(false);
        }
    }

    public function setResizedSize(int $width, int $height) : void
    {
        $this->resize_width = $width;
        $this->resize_height = $height;
        $this->resize_enabled = TRUE;
    }

    /**
     * @param bool $mode
     */
    public function setResizeEnabled(bool $mode) : void
    {
        $this->resize_enabled = $mode;
    }

    public function validate(DataInput $input) : void
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
     * @param StorageObject $object
     * @throws Exception
     */
    public function processObject(StorageObject $object) : void
    {

        if (!($object instanceof ImageStorageObject)) throw new Exception("Invalid argument (Not an ImageStorageObject)");

        debug("UID: " . $object->UID());
        debug("Image dimension: [" . $object->getWidth() . " x " . $object->getHeight() . "]");
        debug("MIME: " . $object->buffer()->mime());
        debug("Length: " . $object->buffer()->length());

        //do not resize during ajax calls. original uploaded file is stored in session
        if (!$this->resize_enabled) {
            debug("Resizing is not enabled for this validator");
            return;
        }

        //final image that goes to DB
        $scaler = new ImageScaler($this->resize_width, $this->resize_height);
        $scaler->setOutputQuality(IMAGE_UPLOAD_STORE_QUALITY);
        $scaler->process($object->buffer());

    }

}

?>
