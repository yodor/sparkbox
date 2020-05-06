<?php
include_once("components/AbstractResultView.php");
include_once("components/renderers/IHeadContents.php");

include_once("utils/ValueInterleave.php");
include_once("components/TableColumn.php");
include_once("components/renderers/cells/TableImageCellRenderer.php");
include_once("components/renderers/cells/CallbackTableCellRenderer.php");
include_once("components/renderers/cells/ActionsTableCellRenderer.php");
include_once("components/renderers/cells/BooleanFieldCellRenderer.php");

include_once("actions/Action.php");

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
        $arr[] = SITE_ROOT . "sparkfront/css/TableView.css";
        return $arr;
    }

    public function addColumn(TableColumn $column)
    {
        $column_name = $column->getFieldName();
        $column->setView($this);
        $this->columns[$column_name] = $column;
    }

    public function insertColumnAfter(TableColumn $column, $after_column_name)
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

    public function removeColumn($field_name)
    {
        if (isset($this->columns[$field_name])) unset($this->columns[$field_name]);
    }

    /**
     * @param $field_name
     * @return TableColumn
     */
    public function getColumn(string $field_name): TableColumn
    {
        return $this->columns[$field_name];
    }

    public function setHeaderCellsEnabled($mode)
    {
        $this->header_cells_enabled = ($mode > 0);
    }

    public function startRender()
    {

        parent::startRender();

        echo "<table class='viewport' >";
        if ($this->header_cells_enabled) {
            echo "<tr class='sort'>";

            foreach (array_keys($this->columns) as $pos => $column_name) {

                $tc = $this->getColumn($column_name);
                $emptyRow = array();
                $tc->getHeaderCellRenderer()->renderCell($emptyRow, $tc);
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

        while ($row = $this->itr->next()) {

            $cls = $v->value();

            echo "<tr class='$cls'>";

            foreach ($this->columns as $column_name => $tc) {

                $cellr = $tc->getCellRenderer();

                $cellr->setClassName($cls);

                $cellr->renderCell($row, $tc);
            }

            echo "</tr>";

            $this->position_index++;

            $v->advance();
        }


    }


}

?>