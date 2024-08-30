<?php
include_once("input/renderers/PlainUpload.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("utils/ImageScaler.php");

class ImageField extends PlainUpload implements IPhotoRenderer
{

    protected int $width = -1;
    protected int $height = 64;

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->setInputAttribute("validator", "image");
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }

    public function renderContents(StorageObject $object) : void
    {

        if ($object instanceof ImageStorageObject) {

            $scaler = new ImageScaler($this->width, $this->height);
            $scaler->setOutputQuality(50);
            $buffer = clone $object->buffer();
            $scaler->process($buffer);

            $image_data = "data:" . $buffer->mime() . ";base64," . $buffer->base64();

            echo "<img class='thumbnail' src='$image_data'>";

        }

    }

}

?>
