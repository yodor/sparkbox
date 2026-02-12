<?php
include_once("responders/json/UploadControlResponder.php");
include_once("input/validators/FileUploadValidator.php");
include_once("input/renderers/InputField.php");

class FileUploadResponder extends UploadControlResponder
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHTML(StorageObject $object, string $field_name) : string
    {

        if (!($object instanceof FileStorageObject)) throw new Exception("Expecting FileStorageObject");

        $filename = $object->getFileName();
        $mime = $object->buffer()->mime();
        $uid = $object->UID();

        Debug::ErrorLog("UID:$uid filename:$filename mime:$mime");

        ob_start();

        echo "<div class='Element' tooltip='$filename'>";
        echo "<span class='thumbnail'><img src='" . Spark::Get(Config::SPARK_LOCAL) . "/images/mimetypes/generic.png'></span>";
        echo "<div class='details'>";
        echo "<span class='filename'><label>$filename</label></span>";
        echo "<span class='filesize'><label>" . Spark::ByteLabel($object->buffer()->length()) . "</label></span>";
        echo "</div>";
        echo "<span class='remove_button' action='Remove'></span>";
        echo "<input type=hidden name='uid_{$field_name}[]' value='$uid' >";
        echo "</div>";

        $html = ob_get_contents();

        ob_end_clean();

        return $html;
    }

    public function validator() : UploadDataValidator
    {
        return new FileUploadValidator();
    }

}