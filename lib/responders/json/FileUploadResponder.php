<?php
include_once("responders/json/UploadControlResponder.php");

include_once("input/validators/FileUploadValidator.php");

include_once("input/renderers/InputField.php");

class FileUploadResponder extends UploadControlResponder
{

    public function __construct()
    {
        parent::__construct("file_upload");
    }

    public function getHTML(FileStorageObject &$storageObject, string $field_name)
    {

        //TODO:prepare other style contents for files. render files as alternating rows icon, filename , type, size, X

        debug("...");

        $filename = $storageObject->getFileName();

        $mime = $storageObject->getMIME();

        $uid = $storageObject->getUID();

        debug("UID:$uid filename:$filename mime:$mime");

        if (!($storageObject instanceof FileStorageObject)) {
            throw new Exception("Incorrect storage object received");
        }

        ob_start();

        //show just icon depending on mime type
        $arr = explode("/", $mime, 2);
        $first = $arr[0];
        echo "<div class='Element' tooltip='$filename'>";
        echo "<span class='thumbnail'><img src='" . SPARK_LOCAL . "/images/mimetypes/generic.png'></span>";
        echo "<div class='details'>";
        echo "<span class='filename'><label>$filename</label></span>";
        echo "<span class='filesize'><label>" . file_size($storageObject->getLength()) . "</label></span>";
        echo "</div>";
        echo "<span class='remove_button' action='Remove'>X</span>";
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

?>
