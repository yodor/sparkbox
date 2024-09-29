<?php
include_once("input/renderers/PlainUpload.php");

class FileField extends PlainUpload
{

    public function __construct(DataInput $input)
    {
        parent::__construct($input);

        $this->input->setAttribute("validator", "file");

    }

    public function renderContents(StorageObject $object) : void
    {

        if ($object->buffer()->length() > 0) {
            echo "<div class='Element' >";

            echo "<span class='thumbnail'><img src='" . SPARK_LOCAL . "/images/mimetypes/generic.png'></span>";

            echo "<div class='details'>";
            echo "<span class='filename'><label>{$object->getFilename()}</label></span>";
            echo "<span class='filesize'><label>" . file_size($object->buffer()->length()) . "</label></span>";
            echo "</div>";

            echo "</div>";
        }

    }

}

?>
