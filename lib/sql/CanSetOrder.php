<?php
include_once("sql/OrderColumn.php");

trait CanSetOrder {

    /**
     * @var array<string, OrderColumn> Collection of column_name/expression => OrderColumn
     */
    protected array $orderParameters = [];

    public function orderColumn(OrderColumn $column) : void
    {
        $this->orderParameters[$column->getName()] = $column;
    }

    public function orderExpression(string $expression) : void
    {
        $column = new OrderColumn($expression);
        $this->orderColumn($column);
    }


    /**
     * Check usage and limit use of rand for result sets with many rows
     * @return void
     */
    public function orderRandom() : void
    {
        $this->orderExpression("rand()");
    }

    /**
     * Proxy method to orderColumn(OrderColumn)
     * @param string $name
     * @param OrderDirection|null $direction null means default ASC
     * @return void
     */
    public function order(string $name, ?OrderDirection $direction=null) : void
    {
        $column = new OrderColumn($name);
        $column->setDirection($direction);
        $this->orderColumn($column);
    }

    public function orderList(string $list) : void
    {
        $names = explode(",", $list);
        $this->orderNames(...$names);
    }

    public function orderNames(string ...$columnList) : void
    {
        foreach ($columnList as $idx => $name) {
            $this->order($name);
        }
    }

    protected function getOrderSQL() : string
    {
        if (count($this->orderParameters)<1) return "";
        $sql = " ORDER BY ";
        $paramSQL = [];
        foreach ($this->orderParameters as $name=> $column) {
            $paramSQL[] = $column->getSQL();
        }
        $sql.= implode(", ", $paramSQL);
        return $sql;
    }

    /**
     * TODO reference of array
     * @param array $other
     * @return void
     */
    protected function copyOrderTo(array &$other) : void
    {
        foreach ($this->orderParameters as $name=> $value) {
            if ($value instanceof OrderColumn) {
                $other[$name] = $value;
            }
        }
    }

}