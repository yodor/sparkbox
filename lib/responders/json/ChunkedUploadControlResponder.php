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

    protected string $fileName = "";
    protected string $cachePath = "";

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

            if (!isset($_POST["fileName"])) throw new Exception("Filename not passed");
            $this->fileName = $_POST["fileName"];

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
     * 1. calls createResponseObject and assign to the object field of $resp
     * 2. storeToSession the $uploadObject
     * 3. increment object_count field of $resp
     *
     * @param JSONResponse $resp
     * @param FileStorageObject $uploadObject
     * @return void
     */
    protected function assignUploadObject(JSONResponse $resp, FileStorageObject $uploadObject) : void
    {
        Debug::ErrorLog("...");

//        //!Do store first
//        //StorageObject can change UID depending on storage type used
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


    protected function getCacheFile(FileStorageObject $uploadObject) : SparkFile
    {

        $cacheFile = new SparkFile();
        $cacheFile->setFilename($uploadObject->UID()."-".$uploadObject->getFilename());
        $cacheFile->setPath($this->cachePath);
        return $cacheFile;
    }

    /**
     * Store upload object
     * Default is to use the StorageObject into session using the StorageObject UID
     * @param FileStorageObject $uploadObject
     * @return void
     */
    protected function storeUploadObject(FileStorageObject $uploadObject) : void
    {
        //store the original data in the session array by the field name and UID
        //

        //set filename as blob is without filename
        $uploadObject->setFilename($this->fileName);

        if ($this->chunkIndex === 0) {
            //data is unique per session. store filename with the first created UID and use later on
            $this->data->set("UID", $uploadObject->UID());
            $this->data->set($uploadObject->UID(), $uploadObject);
        }
        else {
            $firstChunkUID = $this->data->get("UID");
            $uploadObject->setUID($firstChunkUID);
        }

        $cacheFile = $this->getCacheFile($uploadObject);

        Debug::ErrorLog("Using cacheFile: " . $cacheFile->getFilename());

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
            "mime" => $this->getCacheFile($uploadObject)->getMIME(),
            "html" => $html);
    }


    protected function _remove(JSONResponse $resp): void
    {

        Debug::ErrorLog("...");

        if (!isset($_GET[UploadControlResponder::PARAM_UID])) throw new Exception("UID not passed");

        $uid = (string)$_GET[UploadControlResponder::PARAM_UID];

        if (strlen($uid) > 50) throw new Exception("UID maximum size reached");

        $object = $this->data->get($uid);
        if (! ($object instanceof FileStorageObject)) throw new Exception("Incorrect FileStorageObject found for this UID");

        $sparkFile = $this->getCacheFile($object);
        Debug::ErrorLog("Removing cache file: " .$sparkFile->getFilename());
        $sparkFile->remove();

        Debug::ErrorLog("Removing UID[$uid] from session data");
        $this->data->remove($uid);

        Debug::ErrorLog("Finished");

    }

}