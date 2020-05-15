<?php
include_once("components/AbstractResultView.php");
include_once("components/renderers/IHeadContents.php");

include_once("utils/ValueInterleave.php");
include_once("components/TableColumn.php");
include_once("components/renderers/cells/TableImageCellRenderer.php");
include_once("components/renderers/cells/CallbackTableCellRenderer.php");
include_once("components/renderers/cells/ActionsTableCellRenderer.php");
include_once("components/renderers/cells/BooleanFieldCellRenderer.php");

include_once("components/Action.php");

class TableView extends AbstractResultView implements IHeadContents
{

    /**
     * @var array TableColumn
     */
    protected $columns = array();

    protected $header_cells_enabled = TRUE;

    public function __construct(IDataIterator $itr)
    {
        parent::__construct($itr);
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/TableView.css";
        return $arr;
    }

    public function addColumn(TableColumn $column)
    {
        $column_name = $column->getFieldName();
        $column->setView($this);
        $this->columns[$column_name] = $column;
    }

    public function insertColumnAfter(TableColumn $column, string $after_column_name)
    {

        $keys = array_keys($this->columns);
        $index = array_search($after_column_name, $keys);

        $column_name = $column->getFieldName();
        $column->setView($this);

        $this->columns = array_slice($this->columns, 0, $index + 1, TRUE) + array("$column_name" => $column) + array_slice($this->columns, $index + 1, count($this->columns) - 1, TRUE);
    }

    public function resetActionsColumn()
    {
        if (!isset($this->columns["actions"])) return;
        $tc = $this->columns["actions"];
        unset($this->columns["actions"]);

        $this->addColumn($tc);
    }

    public function removeColumn(string $name)
    {
        if (isset($this->columns[$name])) unset($this->columns[$name]);
    }

    /**
     * @param $field_name
     * @return TableColumn
     */
    public function getColumn(string $name): TableColumn
    {
        return $this->columns[$name];
    }

    public function setHeaderCellsEnabled(bool $mode)
    {
        $this->header_cells_enabled = $mode;
    }

    public function startRender()
    {

        parent::startRender();

        echo "<table class='viewport' >";
        if ($this->header_cells_enabled) {
            echo "<tr class='sort'>";

            $names = array_keys($this->columns);
            foreach ($names as $pos => $name) {

                $tc = $this->getColumn($name);
                $emptyRow = array();
                $tc->getHeaderCellRenderer()->setData($emptyRow);
                $tc->getHeaderCellRenderer()->render();
            }

            echo "</tr>";
        }

    }

    public function finishRender()
    {
        echo "</table>";
        parent::finishRender();
    }

    protected function renderImpl()
    {

        $v = new ValueInterleave("even", "odd");
        $this->position_index = 0;

        while ($row = $this->iterator->next()) {

            $cls = $v->value();

            echo "<tr class='$cls'>";

            $names = array_keys($this->columns);
            foreach ($names as $pos => $name) {

                $tc = $this->getColumn($name);

                $cellr = $tc->getCellRenderer();

                $cellr->setClassName($cls);

                $cellr->setData($row);

                $cellr->render();
            }

            echo "</tr>";

            $this->position_index++;

            $v->advance();
        }

    }

}

?>