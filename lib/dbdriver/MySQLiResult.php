<?php
include_once("dbdriver/DBResult.php");

class MySQLiResult extends DBResult
{
    protected ?mysqli_result $result;

    public function __construct(mysqli_result $result)
    {
        $this->result = $result;
    }

    public function __destruct()
    {
        $this->free();
    }

    public function free(): void
    {
        if ($this->result instanceof mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }

    protected function assert_resource() : void
    {
        if (!($this->result instanceof mysqli_result)) throw new Exception("Not a valid mysqli_resource");
    }

    /**
     * Fetch the next row of the result as associative array
     * @return array|null
     * @throws Exception
     */
    public function fetch(): ?array
    {
        $this->assert_resource();
        //null indicates no more records from this resource
        $record = $this->result->fetch_array(MYSQLI_ASSOC);
        if ($record === false) throw new Exception("Error fetching the result");
        return $record;
    }

    /**
     * Fetch the next row of the result wrapped as RawResult
     * @return RawResult|null
     * @throws Exception
     */
    public function fetchResult(): ?RawResult
    {
        $record = $this->fetch();
        if (is_array($record)) return new RawResult($record);
        return null;
    }

    /**
     * Gets the number of rows in the result set
     * @return int
     * @throws Exception
     */
    public function numRows(): int
    {
        $this->assert_resource();
        return $this->result->num_rows;
    }

    /**
     * Returns an array of objects representing the fields in a result set
     * @return array
     * @throws Exception
     */
    public function fields(): array
    {
        $this->assert_resource();
        return $this->result->fetch_fields();
    }
}