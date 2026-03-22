<?php
include_once("objects/SparkObject.php");

enum OrderDirection : string {
    case ASC = "ASC";
    case DESC = "DESC";

    public function opposite(): self
    {
        return match ($this) {
            self::ASC   => self::DESC,
            self::DESC => self::ASC,
        };
    }
}

class OrderColumn extends SparkObject
{
    protected string $label = "";
    protected ?OrderDirection $direction = null;

    static function Labeled(string $name, string $label, ?OrderDirection $direction = null) : OrderColumn
    {
        $column = new OrderColumn($name);
        $column->setLabel(trim($label));
        $column->setDirection($direction);
        return $column;
    }

    public function __construct(string $name, ?OrderDirection $direction = null)
    {
        parent::__construct();
        //no empty name
        $name = trim($name);
        if (!$name) throw new Exception("Empty column name for ordering");
        $this->setName($name);
        $this->setDirection($direction);
    }

    /**
     * Label for this sort column
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Initial ordering direction
     * @return OrderDirection|null
     */
    public function getDirection(): ?OrderDirection
    {
        return $this->direction;
    }

    public function setDirection(?OrderDirection $direction): void
    {
        $this->direction = $direction;
    }

    public function getSQL() : string
    {
        $result = $this->name;

        if ($this->direction) {
            $result.= " ".$this->direction->value;
        }
        return $result;
    }

}