<?php
include_once("responders/json/UploadControlResponder.php");
include_once("storage/FileStorageObject.php");
include_once("utils/SessionData.php");
include_once("storage/SparkFile.php");

abstract class ChunkedUploadControlResponder extends UploadControlResponder
{

    protected int $totalChunks = -1;
    protected int $chunkIndex = -1;
    protected int $chunkSize = -1;

    protected string $cachePath = "";

    protected ?FileCacheEntry $cacheEntry = null;

    /**
     * ChunkedUploadControlResponder constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->cachePath = Spark::Get(Config::CACHE_PATH) . "/chunks/";
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function parseParams() : void
    {
        parent::parseParams();

        if (strcmp($this->requestFunction, "_upload")===0) {
            if (!isset($_POST["totalChunks"])) throw new Exception("Total chunks not passed");
            $this->totalChunks = (int)$_POST["totalChunks"];
            if ($this->totalChunks < 1) throw new Exception("Total chunks invalid");

            if (!isset($_POST["chunkSize"])) throw new Exception("Chunk size not passed");
            $this->chunkSize = (int)$_POST["chunkSize"];
            if ($this->chunkSize < 1) throw new Exception("Chunk size invalid");

            if (!isset($_POST["chunkIndex"])) throw new Exception("Chunk index not passed");
            $this->chunkIndex = (int)$_POST["chunkIndex"];
            if ($this->chunkIndex < 0) throw new Exception("Chunk index invalid (less)");
            if ($this->chunkIndex > $this->totalChunks) throw new Exception("Total chunks invalid (max)");

            if (!file_exists($this->cachePath)) {
                if (!mkdir($this->cachePath, 0755, true)) {
                    throw new Exception("Unable to create the cache store path");
                }
            }

            Debug::ErrorLog("TotalChunks: $this->totalChunks | ChunkSize: $this->chunkSize | ChunkIndex: $this->chunkIndex");
        }
    }

    /**
     * Prepare html contents for the object that was posted via ajax
     * @param StorageObject $object
     * @param string $field_name
     * @return string the html contents
     */
    abstract public function getHTML(StorageObject $object, string $field_name) : string;

    /**
     * Create validator for this upload control
     * @return mixed IInputValidator
     */
    abstract public function validator() : UploadDataValidator;



    /**
     * Assign result to JSONResponse
     * store this chunk to the cache file using storeUploadObject call
     * set chinkIndex to the current received chunkIndex to the response
     * if this is isLastChunk return objectCount (1) and createResponseObject - the html to display back into as Element
     * @param JSONResponse $resp
     * @param FileStorageObject $uploadObject
     * @return void
     * @throws
     */
    protected function assignUploadObject(JSONResponse $resp, FileStorageObject $uploadObject) : void
    {
        Debug::ErrorLog("...");

        //initialize and store UID from the initial chunk
        if ($this->chunkIndex === 0) {

            //store shadow - empty data just UID and filename.
            //After main form is submitted UID can be retrieved and full length file can be accessed from FileCacheEntry
            $object = new FileStorageObject();
            $object->setUID($uploadObject->UID());
            $object->setFilename($uploadObject->getFilename());

            $this->data->set($uploadObject->UID(), $object);
            $this->data->set("UID", $uploadObject->UID());

        }
        else {
            $firstChunkUID = $this->data->get("UID");
            $uploadObject->setUID($firstChunkUID);
        }


        //store chunk contents to the cache file
        $this->storeUploadObject($uploadObject);

        $resp->chunkIndex = $this->chunkIndex;

        Debug::ErrorLog("Is Last Chunk: " . $this->isLastChunk());
        if ($this->isLastChunk()) {
            //prepare representation for this storage object
            $html = $this->getHTML($uploadObject, $this->field_name);

            //JSONResponse returns all dynamically assigned properties in its result
            //create response array with metadata and html
            $resp->objects[] = $this->createResponseObject($uploadObject, $html);

            //JSONResponse.response() returns dynamically assigned properties in its result
            $resp->object_count = count($resp->objects);
        }

    }


    public function getCacheEntry(string $uid) : FileCacheEntry
    {
        if (!$this->cacheEntry) {
            $cacheEntry = CacheFactory::PageCacheEntry($uid);
            if (!($cacheEntry instanceof FileCacheEntry)) throw new Exception("Invalid cacheEntry");
            $this->cacheEntry = $cacheEntry;
        }

        return $this->cacheEntry;
    }

    /**
     * Store each chunk represented in the FileStorageObject to the cache file
     * @param FileStorageObject $uploadObject
     * @return void
     * @throws
     */
    protected function storeUploadObject(FileStorageObject $uploadObject) : void
    {

        $cacheFile = $this->getCacheEntry($uploadObject->UID())->getFile();

        Debug::ErrorLog("Storing chunk [".($this->chunkIndex+1)." of $this->totalChunks] into cacheFile: " . $cacheFile->getFilename());

        try {

            $cacheFile->open("c+b");

            if (!$cacheFile->lock(LOCK_EX)) throw new Exception("Lock failed");

            $seekPosition = $this->chunkIndex * $this->chunkSize;
            $cacheFile->seek($seekPosition, SEEK_SET);

            $bytesWritten = $cacheFile->write($uploadObject->data());

            Debug::ErrorLog("Bytes Written: $bytesWritten");

            // Optional: if this is the final chunk, truncate exactly
            if ($this->isLastChunk()) {
                $finalSize = $seekPosition + $bytesWritten;
                $cacheFile->truncate($finalSize);
            }

            $cacheFile->flush(); // Ensure data is physically written (good practice)

        }
        catch (Exception $e) {
            Debug::ErrorLog($e->getMessage());
            throw $e;
        }
        finally {
            $cacheFile->lock(LOCK_UN);
            $cacheFile->close();
            Debug::ErrorLog("File closed.");
        }
    }

    protected function isLastChunk() : bool
    {
        return ($this->chunkIndex == ($this->totalChunks - 1)) && ($this->totalChunks > 0);
    }
    /**
     *  Create array using FileStorageObject meta-data and html to use as representation in the upload control
     *  HTML is prepared from getHTML
     * @param FileStorageObject $uploadObject
     * @param string $html
     * @return array name=>getFilename(), uid=>UID(), mime=>buffer()->mime(), html=>$html
     */
    protected function createResponseObject(FileStorageObject $uploadObject, string $html) : array
    {
        return array(
            "name" => $uploadObject->getFilename(),
            "uid" => $uploadObject->UID(),
            "mime" => $this->getCacheEntry($uploadObject->UID())->getFile()->getMime(),
            "html" => $html);
    }


    protected function _remove(JSONResponse $resp): void
    {

        parent::_remove($resp);

        $uid = (string)$_GET[UploadControlResponder::PARAM_UID];

        $sparkFile = $this->getCacheEntry($uid)->getFile();
        Debug::ErrorLog("Removing cache file: " .$sparkFile->getFilename());
        $sparkFile->remove();

        $this->data->remove("UID");

    }

}