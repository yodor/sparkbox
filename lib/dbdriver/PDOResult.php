<?php
include_once("dbdriver/DBResult.php");

class PDOResult extends DBResult
{
    /**
     * @var PDOStatement|null
     */
    protected ?PDOStatement $result;

    public function __construct(PDOStatement $result)
    {
        $this->result = $result;
    }

    public function __destruct()
    {
        $this->free();
    }

    /**
     * Frees the result resource and closes the cursor
     */
    public function free(): void
    {
        if ($this->result instanceof PDOStatement) {
            $this->result->closeCursor();
            $this->result = null;
        }
    }

    /**
     * Internal check to ensure the resource is still valid
     * @throws Exception
     */
    protected function assert_resource() : void
    {
        if (!($this->result instanceof PDOStatement)) {
            throw new Exception("Not a valid PDO_resource or result already freed.");
        }
    }

    /**
     * Fetch the next row as an associative array
     * @return array|null
     * @throws Exception
     */
    public function fetch(): ?array
    {
        $this->assert_resource();

        try {
            $record = $this->result->fetch(PDO::FETCH_ASSOC);
            // Returns null when no more records are found
            return ($record !== false) ? $record : null;
        } catch (PDOException $e) {
            throw new Exception("Error fetching the PDO result: " . $e->getMessage());
        }
    }

    /**
     * Fetch the next row wrapped in a RawResult object
     * @return RawResult|null
     */
    public function fetchResult(): ?RawResult
    {
        $record = $this->fetch();
        if (is_array($record)) {
            return new RawResult($record);
        }
        return null;
    }

    /**
     * Returns the number of rows in the result set
     */
    public function numRows(): int
    {
        return ($this->result) ? $this->result->rowCount() : 0;
    }

    /**
     * Returns metadata for the result columns
     * @return array
     */
    public function fields(): array
    {
        $this->assert_resource();
        $fields = [];

        $colCount = $this->result->columnCount();
        for ($i = 0; $i < $colCount; $i++) {
            $meta = $this->result->getColumnMeta($i);
            // Convert to object to maintain compatibility with mysqli->fetch_field()
            $fields[] = (object)[
                'name' => $meta['name'],
                'table' => $meta['table'] ?? '',
                'type' => $meta['native_type'] ?? ''
            ];
        }

        return $fields;
    }
}