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
        $this->createdBy = Debug::Backtrace(-1);

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
    protected function assert_resource(): void
    {
        if (!($this->result instanceof PDOStatement)) {
            throw new Exception("Invalid PDOStatement resource.");
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

            // Ако резултатът е изчерпан (null/false), затваряме автоматично
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
     */
    public function fetchResult(): ?RawResult
    {
        $record = $this->fetch();
        if (is_array($record)) {
            return new RawResult($record);
        }
        return null;
    }

    public function numRows(): int
    {
        // For PDO, rowCount() returns the number of affected rows (INSERT/UPDATE/DELETE)
        return ($this->result instanceof PDOStatement) ? $this->result->rowCount() : 0;
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

    public function isActive() : bool
    {
        return !is_null($this->result);
    }
}