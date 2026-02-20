<?php
include_once("responders/json/ChunkedUploadControlResponder.php");
include_once("input/validators/FileUploadValidator.php");
include_once("input/renderers/InputField.php");

class ChunkedFileUploadResponder extends ChunkedUploadControlResponder
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHTML(StorageObject $object, string $field_name) : string
    {

        if (!($object instanceof FileStorageObject)) throw new Exception("Expecting FileStorageObject");

        $cacheFile = $this->getCacheFile($object);
        $filename = $object->getFilename();
        $mime = $cacheFile->getMIME();
        $uid = $object->UID();

        Debug::ErrorLog("UID:$uid filename:$filename mime:$mime");

        ob_start();

        echo "<div class='Element' tooltip='$filename'>";
        echo "<span class='thumbnail'><img src='" . Spark::Get(Config::SPARK_LOCAL) . "/images/mimetypes/generic.png'></span>";
        echo "<div class='info'>";
            echo "<div class='item filename'><span class='label'>Name</span><span>$filename</span></div>";
            echo "<div class='item filesize'><span class='label'>Size</span><span>" . Spark::ByteLabel($cacheFile->length()) . "</span></div>";
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