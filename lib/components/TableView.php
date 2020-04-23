<?php
include_once("lib/pages/HTMLPage.php");
include_once("lib/components/AbstractResultView.php");

include_once("lib/components/PageResultsPanel.php");

include_once("lib/handlers/ToggleFieldRequestHandler.php");
include_once("lib/handlers/DeleteItemRequestHandler.php");

include_once("lib/components/renderers/cells/TableImageCellRenderer.php");
include_once("lib/components/renderers/cells/CallbackTableCellRenderer.php");
include_once("lib/components/renderers/cells/ActionsTableCellRenderer.php");
include_once("lib/components/renderers/cells/BooleanFieldCellRenderer.php");

include_once("lib/actions/Action.php");

class TableView extends AbstractResultView implements IHeadContents
{


    protected $columns = false;

    protected $header_cells_enabled = true;

    public function __construct(SQLIterator $itr)
    {

        parent::__construct($itr);

        $this->columns = array();


    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/TableView.css";
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

        $this->columns = array_slice($this->columns, 0, $index + 1, true) + array("$column_name" => $column) + array_slice($this->columns, $index + 1, count($this->columns) - 1, true);

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

    public function getColumn($field_name)
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

            foreach ($this->columns as $pos => $tc) {

                $tc->getHeaderCellRenderer()->renderCell(false, $tc);
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

        while ($this->itr->haveMoreResults($row)) {

            $cls = $v->value();

            echo "<tr class='$cls'>";

            foreach ($this->columns as $pos => $tc) {
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
