<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");

/**
 * Handle plain file uploads using the _FILES super array
 * Also handle the virtual input in UploadControlResponder
 */
class UploadDataInput extends InputProcessor
{

    public function loadPostData(array &$arr)
    {

        $name = $this->input->getName();
        debug("DataInput name: " . $name);

        //set default empty file storage
        $this->input->setValue(new FileStorageObject());
        debug("Setting default empty FileStorageObject ...");

        debug("_FILES array keys: " . implode("|", array_keys($_FILES)));

        if (!isset($_FILES[$name])) {
            debug("_FILES array does not have key '$name' - no file uploaded from this control...");
            return;
        }

        debug("Processing _FILES array data");

        if (!is_array($_FILES[$name]["name"])) {
            debug("Processing single uploaded file ...");
            $file_storage = new FileStorageObject();
            $this->processImpl($_FILES[$name], $file_storage);
            $this->input->setValue($file_storage);
            return;
        }

        debug("Processing multiple uploaded files ...");

        $files = array();

        $upload = $this->diverse_array($_FILES[$name]);

        foreach ($upload as $idx => $file) {
            $storage = new FileStorageObject();
            $this->processImpl($file, $storage);
            $files[] = $storage;
        }

        $this->input->setValue($files);

    }

    protected function processImpl($file, FileStorageObject $file_storage)
    {

        debug("Populating FileStorageObject with data from _FILES");

        $upload_status = $file["error"];

        debug("upload_status: " . $upload_status);

        if ($upload_status == UPLOAD_ERR_NO_FILE) {
            debug("No file selected for upload");
            return;
        }

        if ($upload_status !== UPLOAD_ERR_OK) throw new Exception(UploadDataValidator::errString($upload_status));

        $temp_name = $file['tmp_name'];
        if (!is_uploaded_file($temp_name)) throw new Exception("Temp file is not an upload file");

        $file_storage->setData(file_get_contents($temp_name));
        $file_storage->setFilename($file['name']);
        $file_storage->setMIME($file['type']);
        $file_storage->setTimestamp(time());

        debug("FileStorageObject populated. Length: ". $file_storage->getLength() . " | Name: ".$file['name']);

    }

    protected function diverse_array($vector)
    {
        $result = array();
        foreach ($vector as $key1 => $value1) foreach ($value1 as $key2 => $value2) $result[$key2][$key1] = $value2;
        return $result;
    }
}

?>
