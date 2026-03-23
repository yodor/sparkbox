<?php
include_once("storage/BeanDataResponse.php");
include_once("storage/FileStorageObject.php");

class FileDataResponse extends BeanDataResponse
{

    public function __construct(int $id, string $className)
    {
        parent::__construct($id, $className, BeanDataResponse::FIELD_DATA);
    }

    //do nothing StorageObject buffer already contains the data
    protected function process(): void
    {

    }

    protected function cacheName() : string
    {
        $parts = array();
        $parts[] = BeanDataResponse::FIELD_DATA;
        return implode("-", $parts);
    }

}