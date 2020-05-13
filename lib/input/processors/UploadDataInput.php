<?php
include_once("input/processors/InputProcessor.php");
include_once("storage/FileStorageObject.php");

class UploadDataInput extends InputProcessor
{

    public function loadPostData(array &$arr)
    {

        $name = $this->input->getName();

        $file_storage = new FileStorageObject();
        $file_storage->setUploadStatus(UPLOAD_ERR_NO_FILE);

        debug("_FILES array keys: " . implode("|", array_keys($_FILES)));

        if (isset($_FILES[$name])) {


            debug("Processing _FILES value for key: " . $name);

            if (is_array($_FILES[$name]["name"])) {

                debug("Value is array");

                $file_storage = array();

                $upload = $this->diverse_array($_FILES[$name]);

                foreach ($upload as $idx => $file) {
                    $storage = new FileStorageObject();
                    $storage->setUploadStatus(UPLOAD_ERR_NO_FILE);
                    $this->processImpl($file, $storage);
                    $file_storage[] = $storage;

                }
            }
            else {
                debug("Value is not array");
                $this->processImpl($_FILES[$name], $file_storage);
            }

        }
        else {
            debug("Key '$name' not found in _FILES");
        }

        $this->input->setValue($file_storage);

    }

    protected function processImpl($file, FileStorageObject $file_storage)
    {

        $upload_status = $file["error"];

        $file_storage->setUploadStatus($upload_status);

        debug("upload_status: " . $upload_status);

        if ($upload_status === UPLOAD_ERR_OK) {
            $temp_name = $file['tmp_name'];
            $file_storage->setTempName($temp_name);

            //do not set data while tmp_name is valid to lower memory usage
            $file_storage->setData(file_get_contents($temp_name));

            $file_storage->setFilename($file['name']);
            $file_storage->setLength($file['size']);
            $file_storage->setMIME($file['type']);


            if (DB_ENABLED) {
                $file_storage->setTimestamp(DBConnections::Get()->dateTime());
            }
            else {
                $file_storage->setTimestamp(date("Y-m-d H:m:i"));

            }

            $dump = array();
            $dump["Filename"] = $file_storage->getFilename();
            $dump["Length"] = $file_storage->getLength();
            $dump["MIME"] = $file_storage->getMIME();
            $dump["TempName"] = $file_storage->getTempName();

            debug("FileStorageObject populated with data: ", $dump);

        }
    }

    protected function diverse_array($vector)
    {
        $result = array();
        foreach ($vector as $key1 => $value1) foreach ($value1 as $key2 => $value2) $result[$key2][$key1] = $value2;
        return $result;
    }
}

?>
