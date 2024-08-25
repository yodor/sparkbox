<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/FileStorageObject.php");

class FileDataResponse extends BeanDataResponse
{

    protected string $field = "data";

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);
    }

    protected function processData()
    {
        $this->setData($this->row[$this->field], strlen($this->row[$this->field]));
    }

    protected function cacheName() : string
    {
        $parts = array();
        $parts[] = $this->field;
        return implode("-", $parts);
    }
    protected function ETag() : string
    {
        return sparkHash($this->cacheName()."-".$this->getLastModified());
    }
}
