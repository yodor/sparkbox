<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/FileStorageObject.php");

class FileDataResponse extends BeanDataResponse
{

    protected $field = "data";
    protected $disposition = "attachment";

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);
        $this->skip_cache = TRUE;
    }

    protected function processData()
    {
        $this->setData($this->row[$this->field], strlen($this->row[$this->field]));
    }
}
