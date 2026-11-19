<?php
include_once("objects/ISerializable.php");
include_once("objects/IUnserializable.php");
include_once("objects/SparkSealed.php");

include_once("storage/DataBuffer.php");

class StorageObject implements ISparkSeal, ISerializable, IUnserializable
{

    final public const float Serial = 1.0;

    protected string $dataKey = "";

    protected int $timestamp = 0; //unix time
    protected string $uid = "";

    protected DataBuffer $buffer;

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

            $row[$this->dataKey] = $this->buffer->data();

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

    /**
     * The format of __serialize() only - the format before 'Serial' (protected property names, a date string
     * timestamp) is upgraded with tools/storage_upgrade.php
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function __unserialize(array $data): void
    {
        if (!array_key_exists("Serial", $data)) {
            throw new Exception("StorageObject of the old format - run tools/storage_upgrade.php on the database");
        }

        $this->buffer = new DataBuffer();
        $this->buffer->setData((string)$data["data"]);
        $this->timestamp = (int)$data["timestamp"];
        $this->uid = (string)$data["uid"];
    }

    public function wrap(): ISparkUnseal
    {
        return new SparkSealed($this);
    }
}