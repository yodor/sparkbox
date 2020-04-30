<?php
include_once ("lib/storage/BeanDataResponse.php");
include_once ("lib/storage/FileStorageObject.php");

class FileDataResponse extends BeanDataResponse
{
    public $skip_cache = true;
    protected $field = "data";
    protected $disposition = "attachment";

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);

    }

    protected function processData()
    {
        $this->setData($this->row[$this->field], strlen($this->row[$this->field]));
    }
}