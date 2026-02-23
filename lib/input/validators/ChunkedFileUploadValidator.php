<?php
include_once("input/validators/UploadDataValidator.php");

// include_once("dbdriver/DBDriver.php");

class ChunkedFileUploadValidator extends UploadDataValidator
{

    protected ?FileCacheEntry $cacheEntry = null;

    public function __construct()
    {
        parent::__construct();
    }

    //no post-processing
    public function processObject(StorageObject $object) : void
    {

    }

    /**
     * Validate if value is FileStorageObject having UID set and the cacheEntry file exists
     * @param DataInput $input
     * @return StorageObject
     * @throws Exception
     */
    protected function validateObject(DataInput $input) : StorageObject
    {
        $object = parent::validateObject($input);

        if (!($object instanceof FileStorageObject)) throw new Exception("Object is not FileStorageObject");

        $uid = $object->UID();
        if (strlen($uid)<1) throw new Exception("FileStorageObject UID empty");

        $cacheEntry = CacheFactory::PageCacheEntry($uid);
        if (!($cacheEntry instanceof FileCacheEntry)) throw new Exception("CacheFactory error - expected FileCacheEntry");

        if (!$cacheEntry->getFile()->exists()) throw new Exception("File does not exist");

        $this->cacheEntry = $cacheEntry;

        return $object;
    }

    protected function validateSize(DataInput $input, StorageObject $object) : void
    {
        if ($this->cacheEntry->getFile()->length()>0) return;

        Debug::ErrorLog("FileCacheEntry is empty ...");
        if (!$input->isRequired()) return;

        if (!$input->getForm() || $input->getForm()->getEditID() < 1) {
            throw new Exception("No file uploaded");
        }

    }

    protected function getObjectMime(DataInput $input, StorageObject $object) : string
    {
        return $this->cacheEntry->getFile()->getMIME();
    }

}