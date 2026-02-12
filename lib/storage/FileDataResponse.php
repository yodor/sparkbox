<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/FileStorageObject.php");

class FileDataResponse extends BeanDataResponse
{

    protected string $field = BeanDataResponse::FIELD_DATA;

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className);
    }

    //do nothing StorageObject buffer already contains the data
    protected function process(): void
    {

    }

    protected function cacheName() : string
    {
        $parts = array();
        $parts[] = $this->field;
        return implode("-", $parts);
    }

}