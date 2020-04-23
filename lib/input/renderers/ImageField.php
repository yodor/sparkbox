<?php
include_once("lib/input/renderers/UploadField.php");
include_once("lib/components/renderers/IPhotoRenderer.php");

class ImageField extends UploadField implements IPhotoRenderer
{
    protected $render_mode = IPhotoRenderer::RENDER_THUMB;

    protected $width = -1;
    protected $height = 64;


    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("validator", "image");
    }


    public function setThumbnailSize($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getRenderMode()
    {
        return $this->render_mode;
    }

    public function getThumbnailWidth()
    {
        return $this->witdh;
    }

    public function getThumbnailHeight()
    {
        return $this->height;
    }

    public function setRenderMode($rmode)
    {
        $this->render_mode = $rmode;
    }

    public function preparePreviewData(ImageStorageObject $storage_object)
    {
        $data = false;
        $row = array();
        $storage_object->deconstruct($row, "photo", false);

        switch ($this->render_mode) {
            case IPhotoRenderer::RENDER_THUMB:
                $size = max($this->width, $this->height);
                ImageResizer::$max_width = $this->width;
                ImageResizer::$max_height = $this->height;

                ImageResizer::thumbnail($row, $size);
                break;
            case IPhotoRenderer::RENDER_CROP:
                ImageResizer::$max_width = $this->width;
                ImageResizer::$max_height = $this->height;
                ImageResizer::crop($row);
                break;
            default:
                throw new Exception("No render mode set");
        }
        return $row["photo"];

    }

    public function renderContents(StorageObject $storage_object)
    {

        if ($storage_object instanceof ImageStorageObject) {

            //resize
            $raw_data = $this->preparePreviewData($storage_object);

            $image_data = "data:" . $storage_object->getMIME() . ";base64," . base64_encode($raw_data);

            $filename = $storage_object->getFileName();

            $uid = $storage_object->getUID();


            echo "<img class='thumbnail' src='$image_data'>";

        }


    }

}

?>
