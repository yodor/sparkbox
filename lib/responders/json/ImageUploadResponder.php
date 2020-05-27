<?php
include_once("responders/json/UploadControlResponder.php");
include_once("components/renderers/IPhotoRenderer.php");
include_once("utils/ImageScaler.php");
include_once("storage/StorageItem.php");
include_once("input/validators/ImageUploadValidator.php");

class ImageUploadResponder extends UploadControlResponder implements IPhotoRenderer
{

    //   IPhotoRenderer
    protected $width = -1;
    protected $height = -1;

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

    public function __construct(string $cmd = "image_upload")
    {
        parent::__construct($cmd);

        $this->setPhotoSize(-1, 64);

    }

    public function getHTML(FileStorageObject &$object, string $field_name)
    {

        //TODO:prepare other style contents for files. render files as alternating rows icon, filename , type, size, X

        debug("...");

        $filename = $object->getFileName();

        $mime = $object->getMIME();

        $uid = $object->getUID();

        $itemID = $object->id;

        $itemClass = $object->className;

        debug("UID:$uid filename:$filename mime:$mime");

        //gc_collect_cycles();

        if (!($object instanceof FileStorageObject)) {
            throw new Exception("Incorrect storage object received");
        }

        //construct image data in row and pass to ImageScaler to create a temporary thumbnail of the uploaded image.
        $scaler = new ImageScaler($this->width, $this->height);

        $mime = $object->getMIME();

        //data is null during ajax upload. image data can be retrieved from tempName file.
        if ($object->getData()) {
            $scaler->process($object->getData(), $object->getLength(), $mime);
        }
        else {
            $scaler->process(file_get_contents($object->getTempName()), filesize($object->getTempName()), $mime);
        }

        //temporary resize for base64_encode returned in ajax response
        $image_data = "data:$mime;base64," . base64_encode($scaler->getData());

        ob_start();

        $item = new StorageItem();
        $item->className = $object->className;
        $item->id = $object->id;

        echo "<div class='Element' tooltip='$filename' itemID='$itemID' itemClass='$itemClass'>";
        echo "<img class='thumbnail' src='$image_data'>";
        echo "<span class='remove_button' action='Remove'>X</span>";
        echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
        echo "</div>";

        $html = ob_get_contents();

        ob_end_clean();

        //return array("name" => $filename, "uid" => $uid, "mime" => $mime, "html" => $html,);
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

?>
