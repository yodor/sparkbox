<?php
include_once("lib/input/renderers/InputField.php");

class FileField extends PlainUpload
{

    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("validator", "file");

    }

    public function renderContents(StorageObject $object)
    {

        if ($object->getLength()>0) {
            echo "<div class='Element' >";

            echo "<span class='thumbnail'><img src='" . SITE_ROOT . "lib/images/mimetypes/generic.png'></span>";

            echo "<div class='details'>";
            echo "<span class='filename'><label>{$object->getFilename()}</label></span>";
            echo "<span class='filesize'><label>" . file_size($object->getLength()) . "</label></span>";
            echo "</div>";

            echo "</div>";
        }

    }

}

?>