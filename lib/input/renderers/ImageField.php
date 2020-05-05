<?php
include_once("lib/input/renderers/UploadField.php");
include_once("lib/components/renderers/IPhotoRenderer.php");
include_once("lib/utils/ImageScaler.php");


class ImageField extends PlainUpload implements IPhotoRenderer
{

    protected $width = -1;
    protected $height = 64;


    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setFieldAttribute("validator", "image");
    }


    public function setPhotoSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth()
    {
        return $this->width;
    }

    public function getPhotoHeight()
    {
        return $this->height;
    }

    public function renderContents(StorageObject $object)
    {

        if ($object instanceof ImageStorageObject) {

            $scaler = new ImageScaler($this->width, $this->height);
            $scaler->process($object->getData(), $object->getMIME());

            $image_data = "data:" . $object->getMIME() . ";base64," . base64_encode($scaler->getData());

            echo "<img class='thumbnail' src='$image_data'>";

        }

    }

}

?>
