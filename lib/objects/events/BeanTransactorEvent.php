<?php
include_once("objects/SparkEvent.php");
include_once("dbdriver/DBDriver.php");

class BeanTransactorEvent extends SparkEvent
{

    const string BEFORE_COMMIT = "BEFORE_COMMIT";
    const string AFTER_COMMIT = "AFTER_COMMIT";

    /**
     * Current DBDriver for this transactor
     * @var DBDriver|null
     */
    protected ?DBDriver $db = null;

    public function __construct(string $name = "", SparkObject $source = NULL, DBDriver $db = NULL)
    {
        parent::__construct($name, $source);
        $this->db = $db;
    }

    public function setDB(DBDriver $db) : void
    {
        $this->db = $db;
    }

    public function getDB(): ?DBDriver
    {
        return $this->db;
    }

}
?>