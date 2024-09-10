<?php
include_once("utils/IRequestProcessor.php");
include_once("utils/ISQLSelectProcessor.php");
include_once("utils/IGETConsumer.php");
include_once("sql/SQLSelect.php");
include_once("objects/SparkObject.php");

class GETProcessor extends SparkObject implements IRequestProcessor, ISQLSelectProcessor, IGETConsumer
{

    protected string $title = "";

    protected string $value = "";

    protected bool $is_active = false;

    protected ?SQLSelect $select;

    protected ?Closure $closure;

    protected ClauseCollection $collection;

    public function __construct(string $title, string $name)
    {
        parent::__construct();

        $this->title = $title;
        $this->name = $name;

        $this->collection = new ClauseCollection();
        $this->closure = function(GETProcessor $filter) {
            $filter->getClauseCollection()->add($filter->getName(), "'" . $filter->getValue() . "'");
        };

        $this->value = "";
        $this->select = NULL;
    }

    public function setSQLSelect(SQLSelect $select) : void
    {
        $this->select = $select;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * The value of this query variable - already escaped
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function processInput()
    {
        if (isset($_GET[$this->name])) {
            $value = $_GET[$this->name];
            if ($value) {
                $this->value = sanitizeInput($value);
                $this->is_active = true;
                //call closure to work with the clause collection
                if ($this->closure instanceof Closure)($this->closure)($this);
                $this->processClauseCollection();
            }
        }
    }

    public function getClauseCollection() : ClauseCollection
    {
        return $this->collection;
    }

    /**
     * Setting closure to null would make only processing the clause collection
     * @param Closure|null $closure
     * @return void
     */
    public function setClosure(?Closure $closure) : void
    {
        $this->closure = $closure;
    }

    protected function processClauseCollection()
    {
        if ($this->select instanceof SQLSelect) {
            $iterator = $this->collection->iterator();
            while ($clause = $iterator->next()) {
                if (!($clause instanceof SQLClause)) continue;
                $this->select->where()->append($clause);
            }
        }
    }

    /**
     * Return true if request data has loaded into this processor
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->is_active;
    }

    public function getParameterNames(): array
    {
        return array($this->name);
    }

    public function getSQLSelect(): ?SQLSelect
    {
        return $this->select;
    }
}

?>
