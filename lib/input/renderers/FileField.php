<?php
include_once("lib/input/renderers/InputField.php");

class FileField extends UploadField
{

    public function __construct()
    {
        parent::__construct();

        $this->setFieldAttribute("validator", "file");

        //$this->setClassName("FileField PlainUpload UploadControl");

    }

    public function renderContents(StorageObject $storage_object)
    {

        if ($storage_object instanceof FileStorageObject) {

            if ($storage_object->getLength() > 0) {
                $data_uri = "data:" . $storage_object->getMIME() . ";base64, " . base64_encode($storage_object->getData());

                echo "<a href='javascript:window.open(\"$data_uri\");'>Download Existing File</a>";
            }
        }

    }

}

?>