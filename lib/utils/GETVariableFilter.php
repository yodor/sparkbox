<?php
include_once("utils/IRequestProcessor.php");
include_once("sql/SQLSelect.php");

class GETVariableFilter implements IRequestProcessor
{

    protected $title = "";
    protected $name = "";
    protected $value = NULL;
    protected $is_active = FALSE;
    protected $select = NULL;

    public function __construct(string $title, string $name, string $value = "")
    {
        $this->title = $title;
        $this->name = $name;
        $this->value = $value;
        $this->select = NULL;
    }

    public function setSQLSelect(SQLSelect $select)
    {
        $this->select = $select;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName(): string
    {
        return $this->name;
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
            $this->value = DBConnections::Get()->escape($_GET[$this->name]);
            $this->is_active = TRUE;
            $this->processSelect();
        }
    }

    protected function processSelect()
    {
        if ($this->select instanceof SQLSelect) {
            $this->select->where()->add($this->name, "'" . $this->value . "'");
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

}

?>