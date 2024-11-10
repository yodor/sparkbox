<?php
include_once("components/AbstractResultView.php");
include_once("components/renderers/IHeadContents.php");

include_once("utils/ValueInterleave.php");
include_once("components/TableColumn.php");
include_once("components/renderers/cells/ImageCell.php");
include_once("components/renderers/cells/ActionsCell.php");
include_once("components/renderers/cells/BooleanCell.php");

include_once("components/Action.php");

class TableView extends AbstractResultView implements IHeadContents
{

    /**
     * @var array TableColumn
     */
    protected array $columns = array();


    public function __construct(IDataIterator $itr)
    {
        parent::__construct($itr);

        $this->header->setRenderEnabled(false);
        $this->list_empty->setContents("No elements in this collection yet");

    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/TableView.css";
        return $arr;
    }

    public function addColumn(TableColumn $column): void
    {
        $column_name = $column->getName();
        $column->setView($this);
        $this->columns[$column_name] = $column;
    }

    public function insertColumn(TableColumn $column, string $after_column_name): void
    {

        $keys = array_keys($this->columns);
        $index = array_search($after_column_name, $keys);

        $column_name = $column->getName();
        $column->setView($this);

        $this->columns = array_slice($this->columns, 0, $index + 1, TRUE) + array("$column_name" => $column) + array_slice($this->columns, $index + 1, count($this->columns) - 1, TRUE);
    }

    public function resetActionsColumn(): void
    {
        if (!isset($this->columns["actions"])) return;
        $tc = $this->columns["actions"];
        unset($this->columns["actions"]);

        $this->addColumn($tc);
    }

    public function removeColumn(string $name): void
    {
        if (isset($this->columns[$name])) unset($this->columns[$name]);
    }

    /**
     * @param string $name Column name
     * @return TableColumn
     */
    public function getColumn(string $name): TableColumn
    {
        return $this->columns[$name];
    }

    public function haveColumn(string $name): bool
    {
        return array_key_exists($name, $this->columns);
    }


    protected function processAttributes(): void
    {
        $columnCount = count(array_keys($this->columns));

        $this->viewport->setStyle("grid-template-columns", "repeat($columnCount, auto)");

        parent::processAttributes();
    }

    protected function renderItems() : void
    {

        $names = array_keys($this->columns);
        foreach ($names as $pos => $name) {

            $column = $this->getColumn($name);
//
//            $emptyRow = array();
//
            $header = $column->getHeaderCellRenderer();
//            if ($column->getAlignClass()) {
//                $header->addClassName($column->getAlignClass());
//            }
//            $header->setData($emptyRow);
            $header->render();
        }

        parent::renderItems();
    }
    protected function renderItem(RawResult $result) : void
    {
        static $v = new ValueInterleave();

        $names = array_keys($this->columns);

        foreach ($names as $pos => $name) {

            $column = $this->getColumn($name);

            $cell = $column->getCellRenderer();

            if ($column->getAlignClass()) {
                $cell->addClassName($column->getAlignClass());
            }

            $cell->setData($result->toArray());
            $cell->setAttribute("title", $column->getName());
            $cell->setAttribute("parity", $v->value());
            $cell->render();
        }

        $v->advance();
    }
}

?>
