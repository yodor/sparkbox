<?php
include_once("dbdriver/DBResult.php");

class PDOResult extends DBResult
{

    public string $createdBy = '';

    /**
     * @var PDOStatement|null
     */
    protected ?PDOStatement $result = null;

    public function __construct(PDOStatement $result)
    {
        $this->result = $result;
        //$this->createdBy = Debug::Backtrace(-1);
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
     * Fetch the next row as an associative array
     * @return array|null
     * @throws Exception
     */
    public function fetch(): ?array
    {
        if (!$this->result) {
            return null;
        }

        try {
            $record = $this->result->fetch(PDO::FETCH_ASSOC);

            //no more records in this result-set - $record is (null/false) - free the result - isActive = false
            if ($record === false) {

                $error = $this->result->errorInfo();
                if (!empty($error[2])) {
                    Debug::ErrorLog("PDO Fetch Warning: " . $error[2]);
                }

                $this->free();
                return null;
            }

            return $record;

        } catch (PDOException $e) {
            $this->free(); // Затваряме и при грешка, за да не "забие" драйвера
            throw new Exception("PDO Fetch Error: " . $e->getMessage());
        }

    }

    /**
     * Fetch the next row wrapped in a RawResult object
     * @return RawResult|null
     * @throws Exception
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
     * Return the affected row count during (INSERT/UPDATE/DELETE) or -1 if this is a select
     *
     * @return int
     */
    public function affectedRows(): int
    {
        if ($this->isActive()) return -1;

        //Returns the number of affected rows (INSERT/UPDATE/DELETE) - for SELECT not available
        return $this->result->rowCount();
    }

    /**
     * Returns metadata for the result columns. (INSERT/UPDATE/DELETE) - return empty array here
     * @return array
     */
    public function fields(): array
    {
        if (!$this->isActive()) return [];

        $fields = [];

        $colCount = $this->result->columnCount();
        for ($i = 0; $i < $colCount; $i++) {
            $meta = $this->result->getColumnMeta($i);
            // Convert to object to maintain compatibility with mysqli->fetch_field()
            $fields[] = (object)[
                'name' => $meta['name'],
                'type' => $meta['native_type'] ?? ''
            ];
        }

        return $fields;
    }

    /**
     * True if this statement has result-set ready to fetch ie $this->result->columnCount() > 0
     * @return bool
     */
    public function isActive() : bool
    {
        // This is a SELECT / SHOW / EXPLAIN / DESCRIBE / CALL returning rows
        // Most reliable way to check for result set
        return !is_null($this->result) && ($this->result->columnCount() > 0);
    }
}