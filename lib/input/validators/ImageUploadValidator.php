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

        $this->accept_mimes = ImageScaler::SupportedMimes;

        if (Spark::GetBoolean(Config::IMAGE_UPLOAD_UPSCALE) || Spark::GetBoolean(Config::IMAGE_UPLOAD_DOWNSCALE)) {
            $this->setResizedSize(Spark::GetInteger(Config::IMAGE_UPLOAD_DEFAULT_WIDTH), Spark::GetInteger(Config::IMAGE_UPLOAD_DEFAULT_HEIGHT));
        }
        else {
            $this->setResizeEnabled(false);
        }
    }

    public function setAcceptMimes(array $accept_mimes) : void
    {
        foreach ($accept_mimes as $mime) {
            $validMime = ImageType::tryFrom($mime);
            if ($validMime === null) throw new Exception("Unsupported mime: " . $mime);
        }
        $this->accept_mimes = $accept_mimes;
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

        Debug::ErrorLog("Input: " . $input->getName());

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

        Debug::ErrorLog("UID: " . $object->UID());
        Debug::ErrorLog("Image dimension: [" . $object->getWidth() . " x " . $object->getHeight() . "]");
        Debug::ErrorLog("MIME: " . $object->buffer()->mime());
        Debug::ErrorLog("Length: " . $object->buffer()->length());

        //do not resize during ajax calls. original uploaded file is stored in session
        if (!$this->resize_enabled) {
            Debug::ErrorLog("Resizing is not enabled for this validator");
            return;
        }

        //the stored original fits inside the box (IMAGE_UPLOAD_DEFAULT_WIDTH x HEIGHT), proportions kept: a larger
        //image is made smaller (IMAGE_UPLOAD_DOWNSCALE), a smaller one larger (IMAGE_UPLOAD_UPSCALE)
        $width = $object->getWidth();
        $height = $object->getHeight();
        $larger = ($this->resize_width > 0 && $width > $this->resize_width) || ($this->resize_height > 0 && $height > $this->resize_height);
        $smaller = ($this->resize_width < 1 || $width < $this->resize_width) && ($this->resize_height < 1 || $height < $this->resize_height);
        $downscale = Spark::GetBoolean(Config::IMAGE_UPLOAD_DOWNSCALE) && $larger;
        $upscale = Spark::GetBoolean(Config::IMAGE_UPLOAD_UPSCALE) && $smaller;
        if (!$downscale && !$upscale) return;

        //final image that goes to DB
        $scaler = new ImageScaler($this->resize_width, $this->resize_height);
        $scaler->setUpscaleEnabled($upscale);
        $scaler->setOutputQuality(Spark::GetInteger(Config::IMAGE_UPLOAD_STORE_QUALITY));
        $scaler->process($object->buffer());
        //the width and height of the stored image (the object kept the uploaded ones)
        $object->setData($object->buffer()->data());
    }

}