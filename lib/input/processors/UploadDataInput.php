<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");

/**
 * Handle plain file uploads using the _FILES super array
 * Also handle the virtual input in UploadControlResponder
 */
class UploadDataInput extends InputProcessor
{

    public function loadPostData(array $data) : void
    {

        $name = $this->input->getName();
        Debug::ErrorLog("DataInput name: " . $name);

        //set default empty file storage
        $this->input->setValue(new FileStorageObject());
        Debug::ErrorLog("Setting default empty FileStorageObject ...");

        Debug::ErrorLog("_FILES array keys: " . implode("|", array_keys($_FILES)));
        if (!isset($_FILES[$name])) {
            Debug::ErrorLog("_FILES array does not have key '$name' - no file uploaded from this control...");
            return;
        }

        Debug::ErrorLog("Processing _FILES array data");
        if (!is_array($_FILES[$name]["name"])) {
            Debug::ErrorLog("Processing single uploaded file ...");
            $file_storage = new FileStorageObject();
            $this->processImpl($_FILES[$name], $file_storage);
            $this->input->setValue($file_storage);
            return;
        }

        Debug::ErrorLog("Processing multiple uploaded files ...");
        //reformat array keys
        $uploaded_files = $this->diverse_array($_FILES[$name]);

        $files = array();
        foreach ($uploaded_files as $idx => $file) {
            $storage = new FileStorageObject();
            $this->processImpl($file, $storage);
            $files[] = $storage;
        }

        $this->input->setValue($files);

    }

    protected function processImpl($file, FileStorageObject $object) : void
    {

        Debug::ErrorLog("Populating FileStorageObject with data from _FILES");

        $upload_status = $file["error"];

        Debug::ErrorLog("upload_status: " . $upload_status);

        if ($upload_status == UPLOAD_ERR_NO_FILE) {
            Debug::ErrorLog("No file selected for upload");
            return;
        }

        if ($upload_status !== UPLOAD_ERR_OK) throw new Exception(UploadDataValidator::errString($upload_status));

        $temp_name = $file['tmp_name'];
        if (!is_uploaded_file($temp_name)) throw new Exception("File is not an upload file");

        $object->setData(file_get_contents($temp_name));
        $object->setFilename($file['name']);
        $object->setTimestamp(time());

        Debug::ErrorLog("FileStorageObject populated. Length: ". $object->buffer()->length() . " | Name: ".$file['name']);

    }

    protected function diverse_array($vector)
    {
        $result = array();
        foreach ($vector as $key1 => $value1) foreach ($value1 as $key2 => $value2) $result[$key2][$key1] = $value2;
        return $result;
    }
}

?>
