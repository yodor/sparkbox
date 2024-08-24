<?php

class StorageObject
{

    final public const Serial = 1.0;

    protected string $dataKey = "";
    protected string $data = "";
    protected int $length = 0;
    protected int $timestamp = 0; //unix time
    protected string $uid = "";

    protected bool $compatUnserialize = false;

    public function __construct()
    {
        $this->dataKey = "data";
        $this->data = "";
        $this->length = 0;
        $this->timestamp = 0;
        $this->uid = microtime(TRUE) . "." . rand();
    }

    public function setTimestamp(int $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp() : int
    {
        return $this->timestamp;
    }

    public function getData() : string
    {
        return $this->data;
    }

    public function getLength() : int
    {
        return $this->length;
    }

    public function setData(string $data)
    {
        $this->data = $data;
        $this->length = strlen($data);
    }

    public function haveData() : bool
    {
        return (strlen($this->data) > 0);
    }

    public function serializeDB()
    {

        return DBConnections::Get()->escape(serialize($this));

    }

    public function getUID() : string
    {
        return $this->uid;
    }

    public function setUID(string $uid)
    {
        $this->uid = $uid;
    }

    public function setDataKey(string $dataKey) : void
    {
        $this->dataKey = $dataKey;
    }

    public function getDataKey(): string
    {
        return $this->dataKey;
    }

    public function deconstruct(array &$row, $doEscape = TRUE) : void
    {
        $row[$this->dataKey] = $this->data;

        if ($doEscape) {

            $row[$this->dataKey] = DBConnections::Get()->escape($this->data);

        }
        $row["size"] = $this->length;
        $row["timestamp"] = $this->timestamp;
    }

    public static function reconstruct(&$row, $field_name): StorageObject
    {

        $storage_object = NULL;
        if (isset($row["mime"]) && isset($row["size"]) && isset($row[$field_name]) && isset($row["filename"]) && isset($row["date_upload"])) {

            debug("StorageObject::reconstruct | Found needed array key to reconstruct a storage object");

            if (isset($row["width"]) && isset($row["height"])) {
                $storage_object = new ImageStorageObject();
                $storage_object->setData($row[$field_name]);
                debug("StorageObject::reconstruct | Reconstructed ImageStorageObject: Dimensions (" . $row["width"] . "x" . $row["height"] . ")");
            }
            else {
                $storage_object = new FileStorageObject();
                $storage_object->setData($row[$field_name]);
                debug("StorageObject::reconstruct | Reconstructed FileStorageObject");
            }

            $storage_object->setMIME($row["mime"]);
            $storage_object->setFilename($row["filename"]);
            $storage_object->setTimestamp(strtotime($row["date_upload"]));



            $reconstructed_uid = $row[$field_name] . $row["mime"] . "|" . $row["size"] . "|" . $row["filename"];

            $storage_object->setUID($storage_object->getTimestamp() . "." . md5($reconstructed_uid));

            debug("StorageObject::reconstruct | Reconstructed properties: UID: " . $storage_object->getUID() . " MIME: " . $row["mime"] . " Filename: " . $row["filename"] . " Length: " . $row["size"]);

            return $storage_object;
        }
        else {
            throw new Exception("Unable to reconstruct from this row. Required fields not found");
        }

    }


    public function __serialize(): array
    {
        $result = array();
        $result["Serial"] = StorageObject::Serial;

        $result["data"] = $this->data;
        $result["length"] = $this->length;
        $result["timestamp"] = $this->timestamp;
        $result["uid"] = $this->uid;

        return $result;
    }

    public function __unserialize(array $data): void
    {
        if (!array_key_exists("Serial", $data)) {
            $this->compatUnserialize = true;
            debug("Using compatibility key names");
        }

        $this->data = (string)$data[$this->keyName("data")];
        $this->length = (int)$data[$this->keyName("length")];
        $value = $data[$this->keyName("timestamp")];
        $timestamp = strtotime($value);
        if ($timestamp === FALSE) {
            $timestamp = intVal($value);
        }
        $this->timestamp = $timestamp;

        $this->uid = (string)$data[$this->keyName("uid")];
    }

    protected function keyName(string $keyName) : string
    {
        if ($this->compatUnserialize) {
            return "\0*\0".$keyName;
        }
        else {
            return $keyName;
        }
    }

}

?>
