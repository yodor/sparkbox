<?php
include_once("storage/CacheEntry.php");
include_once("beans/SparkCacheBean.php");

class DBCacheEntry extends CacheEntry
{
    /**
     * Cache Entry UID
     * @var string
     */
    protected string $cacheName = "";

    /**
     * Bean class name
     * @var string
     */
    protected string $className = "";

    /**
     * Bean id
     * @var int
     */
    protected int $beanID = -1;

    protected ?SparkCacheBean $bean = null;

    protected ?RawResult $result = null;

    protected int $resultCount = -1;

    /**
     * Cache entry primary key value
     * @var int
     */
    protected int $entryID = -1;

    public function __construct(string $cacheName, string $className, int $beanID)
    {
        parent::__construct();
        Debug::ErrorLog("CacheName: $cacheName | ClassName: $className | BeanID: $beanID");

        $this->cacheName = $cacheName;
        $this->className = $className;
        $this->beanID = $beanID;

        $this->bean = new SparkCacheBean();
        $this->queryCache();
    }

    protected function queryCache() : void
    {
        Debug::ErrorLog("Doing cache query ...");
        $query = $this->bean->queryFull();
        $query->select->where()->add("cacheName", "'{$this->cacheName}'");
        $query->select->where()->add("className", "'{$this->className}'");
        $query->select->where()->add("beanID", $this->beanID);

        //should be only one match

        $this->resultCount = $query->exec();
        Debug::ErrorLog("Result count: ".$this->resultCount);

        if ($this->resultCount > 0) {
            $this->result = $query->nextResult();
            $this->entryID = (int)$this->result->get($this->bean->key());
            Debug::ErrorLog("EntryID: ".$this->entryID);
        }
    }

    public function haveData(): bool
    {
        return $this->entryID>0;
    }

    public function output(): void
    {
        echo $this->result->get("data");
    }

    public function store(string $data, int $lastModified = 0): void
    {
        if ($this->haveData()) {
            $data = array("data" => $this->bean->getDB()->escape($data), "lastModified" => $lastModified);
            $this->bean->update($this->entryID, $data);
        }
        else {
            $data = array(
                "cacheName" => $this->bean->getDB()->escape($this->cacheName),
                "className" => $this->bean->getDB()->escape($this->className),
                "beanID" => $this->beanID,
                "data" => $this->bean->getDB()->escape($data),
                "lastModified" => $lastModified
            );
            $this->entryID = $this->bean->insert($data);
        }
        $this->queryCache();
    }

    public function storeBuffer(DataBuffer $data, int $lastModified = 0): void
    {
        $this->store($data->data(), $lastModified);
    }

    public function lastModified(): int
    {
        return $this->result->get("lastModified");
    }

    public function getBuffer() : DataBuffer
    {
        $buffer = new DataBuffer();
        $buffer->setData($this->result->get("data"));
        return $buffer;
    }

    public function remove() : void
    {
        $this->bean->delete($this->entryID);
    }
}