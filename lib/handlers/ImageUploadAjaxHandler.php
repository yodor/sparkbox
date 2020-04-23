<?php
include_once("lib/handlers/UploadControlAjaxHandler.php");
include_once("lib/components/renderers/IPhotoRenderer.php");

include_once("lib/input/validators/ImageUploadValidator.php");

class ImageUploadAjaxHandler extends UploadControlAjaxHandler implements IPhotoRenderer
{

    //   IPhotoRenderer
    protected $width = -1;
    protected $height = -1;
    protected $render_mode = IPhotoRenderer::RENDER_CROP;

    public function setThumbnailSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setRenderMode($mode)
    {
        $this->render_mode = $mode;
    }

    public function getRenderMode()
    {
        return $this->render_mode;
    }

    public function getThumbnailWidth()
    {
        return $this->width;
    }

    public function getThumbnailHeight()
    {
        return $this->height;
    }

    public function __construct()
    {
        parent::__construct("image_upload");

        //previews of uploaded images are thumbnails with autofit to width 64
        $this->setThumbnailSize(-1, 64);

    }


    public function getHTML(FileStorageObject &$storageObject, string $field_name)
    {

        //TODO:prepare other style contents for files. render files as alternating rows icon, filename , type, size, X

        debug("ImageUploadAjaxHandler::createUploadContents() ...");

        $filename = $storageObject->getFileName();

        $mime = $storageObject->getMIME();

        $uid = $storageObject->getUID();

        $itemID = $storageObject->itemID;

        $itemClass = $storageObject->itemClass;

        debug("ImageUploadAjaxHandler::createUploadContents() UID:$uid filename:$filename mime:$mime");

        //gc_collect_cycles();

        ob_start();
        if (!($storageObject instanceof FileStorageObject)) {
            throw new Exception("Incorrect storage object received");
        }

        //construct image data in row and pass to ImageResizer to create a temporary thumbnail of the uploaded image.
        $row = array();

        $row["mime"] = $storageObject->getMIME();

        //data is null during ajax upload. image data can be retrieved from tempName file.
        if ($storageObject->getData()) {
            $row["photo"] = $storageObject->getData();
        }
        else {
            $row["photo"] = file_get_contents($storageObject->getTempName());
        }
        //temporary resize for base64_encode returned in ajax response
        ImageResizer::$max_width = $this->getThumbnailWidth();
        ImageResizer::$max_height = $this->getThumbnailHeight();
        ImageResizer::crop($row);

        $image_data = "data:$mime;base64," . base64_encode($row["photo"]);
        unset($row);


        echo "<div class='Element' tooltip='$filename' itemID='$itemID' itemClass='$itemClass'>";
        echo "<a target='_itemGallery' href='" . SITE_ROOT . "storage.php?cmd=gallery_photo&class=$itemClass&id=$itemID'>";
        echo "<img class='thumbnail' src='$image_data'>";
        echo "</a>";
        echo "<span class='remove_button' action='Remove'>X</span>";
        echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
        echo "</div>";

        $html = ob_get_contents();

        ob_end_clean();

        //return array("name" => $filename, "uid" => $uid, "mime" => $mime, "html" => $html,);
        return $html;
    }

    public function validator()
    {
        $validator = new ImageUploadValidator();
        //turn off resizing during ajax calls. resizing will be done on the final submit of form
        $validator->setResizeEnabled(false);
        //$validator->setResizedSize($this->width, $this->height);
        return $validator;
    }


}

?>
