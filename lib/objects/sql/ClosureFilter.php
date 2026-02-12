<?php
include_once("sql/SQLSelect.php");
include_once("input/DataInput.php");

class ClosureFilter extends SparkObject
{
    protected string $title = "";
    protected ?Closure $callback = null;

    protected bool $processed = false;

    const int MATCH_LIKE = 1;
    const int MATCH_EQUAL = 2;

    protected int $matchMode = ClosureFilter::MATCH_LIKE;

    public function __construct(string $title, Closure $callback)
    {
        parent::__construct();
        $this->title = $title;
        $this->callback = $callback;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setClosure(Closure $callback): void
    {
        $this->callback = $callback;
    }
    public function getClosure(): Closure
    {
        return $this->callback;
    }

    public function process(SQLSelect $select, DataInput $input): void
    {
        if ($this->processed) return;

        if ($input->getValue()) {
            ($this->callback)($select, $input);
            $this->processed = true;
        }
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setMatchMode(int $mode) : void
    {
        $this->matchMode = $mode;
    }
    public function getMatchMode(): int
    {
        return $this->matchMode;
    }
    public function getMatchOperator(DataInput $input) : string
    {
        $result = "";
        switch ($this->matchMode) {
            case ClosureFilter::MATCH_EQUAL:
                $result = " = ";
                break;
            case ClosureFilter::MATCH_LIKE:
                $result = " LIKE ";
                break;

        }
        return $result;
    }

    public function getMatchValue(DataInput $input)
    {
        $value = $input->getValue();
        if ($this->matchMode==ClosureFilter::MATCH_LIKE) {
            $value = "%$value%";
        }
        return $value;
    }
}