<?php
include_once("lib/input/validators/UploadDataValidator.php");

// include_once("lib/dbdriver/DBDriver.php");

class FileUploadValidator extends UploadDataValidator
{

    protected function processUploadData(DataInput $field)
    {


    }

    public function process(FileStorageObject $storage_object)
    {

    }
}

?>