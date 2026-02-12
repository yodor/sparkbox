<?php
include_once("storage/DataBuffer.php");

class StorageObject
{

    final public const float Serial = 1.0;

    protected string $dataKey = "";

    protected int $timestamp = 0; //unix time
    protected string $uid = "";

    protected DataBuffer $buffer;

    protected bool $compatUnserialize = false;

    public function __construct()
    {
        $this->dataKey = "data";
        $this->buffer = new DataBuffer();
        $this->timestamp = 0;
        $this->uid = microtime(TRUE) . "." . rand();
    }

    public function setTimestamp(int $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }

    public function timestamp() : int
    {
        return $this->timestamp;
    }

    public function data() : string
    {
        return $this->buffer->data();
    }

    public function setData(string $data) : void
    {
        $this->buffer->setData($data);
    }

    public function buffer() : DataBuffer
    {
        return $this->buffer;
    }

    public function serializeDB() : string
    {

        return DBConnections::Open()->escape(serialize($this));

    }

    public function UID() : string
    {
        return $this->uid;
    }

    public function setUID(string $uid) : void
    {
        $this->uid = $uid;
    }

    public function setDataKey(string $dataKey) : void
    {
        $this->dataKey = $dataKey;
    }

    public function dataKey(): string
    {
        return $this->dataKey;
    }

    public function deconstruct(array &$row, $doEscape = TRUE) : void
    {
        $row[$this->dataKey] = $this->buffer->data();

        if ($doEscape) {

            $row[$this->dataKey] = DBConnections::Open()->escape($this->buffer->data());

        }
        $row["mime"] = $this->buffer->mime();
        $row["size"] = $this->buffer->length();
        $row["timestamp"] = $this->timestamp;
        $row["uid"] = $this->uid;
    }

    /**
     * Create storage object form DB result row
     *
     * @param array $result
     * @param string $blob_field
     * @return StorageObject
     * @throws Exception
     */
    public static function CreateFrom(array $result, string $blob_field): StorageObject
    {

        $object = NULL;

        if (!(isset($result["mime"]) && isset($result["size"]) && isset($result[$blob_field]) && isset($result["filename"]) && isset($result["date_upload"]))) {
            throw new Exception("Unable to reconstruct from this row. Required fields not found");
        }

        Debug::ErrorLog("Found needed array keys to create StorageObject");

        if (isset($result["width"]) && isset($result["height"])) {
            $object = new ImageStorageObject();
            $object->buffer()->setData($result[$blob_field]);
        }
        else {
            $object = new FileStorageObject();
            $object->buffer()->setData($result[$blob_field]);
        }

        $object->setFilename($result["filename"]);

        if (isset($result["date_updated"])) {
            $object->setTimestamp(strtotime($result["date_updated"]));
        }
        else {
            $object->setTimestamp(strtotime($result["date_upload"]));
        }

        $object->setUID(Spark::Hash($object->getFilename()."|".$object->timestamp()));

        Debug::ErrorLog("Reconstructed: ". get_class($object).
            " UID: " . $object->UID() .
            " MIME: " . $object->buffer()->mime() .
            " Length: " . $object->buffer->length()) .
            " Filename: " . $object->getFilename();

        return $object;

    }


    public function __serialize(): array
    {
        $result = array();
        $result["Serial"] = StorageObject::Serial;

        $result["data"] = $this->buffer->data();

        $result["timestamp"] = $this->timestamp;
        $result["uid"] = $this->uid;

        return $result;
    }

    public function __unserialize(array $data): void
    {
        if (!array_key_exists("Serial", $data)) {
            $this->compatUnserialize = true;
            Debug::ErrorLog("Using compatibility key names");
        }

        $this->buffer = new DataBuffer();

        $this->buffer->setData((string)$data[$this->keyName("data")]);
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