<?php
include_once("responders/json/UploadControlResponder.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("utils/ImageScaler.php");
include_once("storage/StorageItem.php");
include_once("input/validators/ImageUploadValidator.php");

class ImageUploadResponder extends UploadControlResponder implements IPhotoRenderer
{

    //   IPhotoRenderer
    protected int $width = -1;
    protected int $height = -1;

    public function setPhotoSize(int $width, int $height): void
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

    public function __construct()
    {
        parent::__construct();

        //default thumbnail size
        $this->setPhotoSize(-1, 64);

    }

    public function getHTML(StorageObject $object, string $field_name) : string
    {
        if (!($object instanceof FileStorageObject)) throw new Exception("Expecting FileStorageObject");

        $filename = $object->getFileName();
        $mime = $object->buffer()->mime();
        $uid = $object->UID();

        Debug::ErrorLog("UID:$uid filename:$filename mime:$mime");

        //create a temporary thumbnail of the uploaded image
        $scaler = new ImageScaler($this->width, $this->height);
        $scaler->setOutputQuality(50);
        //copy upload data to new buffer
        $buffer = clone $object->buffer();
        $scaler->process($buffer);
        $image_data = "data:$mime; base64," . $buffer->base64();

        ob_start();
        echo "<div class='Element' tooltip='$filename' >";
        echo "<img class='thumbnail' src='$image_data'>";
        echo "<span class='remove_button' action='Remove'></span>";
        echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
        echo "</div>";
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function validator() : UploadDataValidator
    {
        $validator = new ImageUploadValidator();
        //turn off resizing during ajax calls. resizing will be done on the final submit of the form
        $validator->setResizeEnabled(FALSE);
        return $validator;
    }

}