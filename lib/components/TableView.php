<?php
include_once("components/AbstractResultView.php");
include_once("components/renderers/IHeadContents.php");

include_once("utils/ValueInterleave.php");
include_once("components/TableColumn.php");
include_once("components/renderers/cells/ImageCellRenderer.php");
include_once("components/renderers/cells/CallbackCellRenderer.php");
include_once("components/renderers/cells/ActionsCellRenderer.php");
include_once("components/renderers/cells/BooleanCellRenderer.php");

include_once("components/Action.php");

class TableView extends AbstractResultView implements IHeadContents
{

    /**
     * @var array TableColumn
     */
    protected $columns = array();

    protected $header_cells_enabled = TRUE;

    /**
     * @var Component
     */
    protected $row;

    /**
     * @var Component
     */
    protected $header_row;

    /**
     * @var Component
     */
    protected $table;

    /**
     * @var string Message shown if the table source list is empty
     */
    protected $list_empty_message;

    public function __construct(IDataIterator $itr)
    {
        parent::__construct($itr);
        $this->enablePaginators(TableView::PAGINATOR_BOTTOM);

        $this->row = new Component();
        $this->row->setTagName("TR");
        $this->row->setComponentClass("");

        $this->header_row = new Component();
        $this->header_row->setTagName("TR");
        $this->header_row->setComponentClass("sort");

        $this->table = new Component();
        $this->table->setTagName("TABLE");
        $this->table->setComponentClass("viewport");

        $this->list_empty_message = tr("No elements in this collection yet");
    }

    public function setListEmptyMessage(string $message)
    {
        $this->list_empty_message = $message;
    }

    public function getListEmptyMessage(): string
    {
        return $this->list_empty_message;
    }

    public function requiredStyle() : array
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

    public function insertColumn(TableColumn $column, string $after_column_name)
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

    /**
     * Enable/Disable rendering of the Table Header Cells row
     * @param bool $mode
     */
    public function setHeaderCellsEnabled(bool $mode)
    {
        $this->header_cells_enabled = $mode;
    }

    public function startRender()
    {

        //will render top paginator if enabled
        parent::startRender();

        if ($this->total_rows<1 && strlen($this->list_empty_message)>0) {
            echo "<div class='caption empty_list'>";
            echo $this->list_empty_message;
            echo "</div>";
        }

        $this->table->startRender();

        if ($this->header_cells_enabled) {

            $this->header_row->startRender();

            $names = array_keys($this->columns);
            foreach ($names as $pos => $name) {

                $column = $this->getColumn($name);

                $emptyRow = array();

                $header = $column->getHeaderCellRenderer();
                if ($column->getAlignClass()) {
                    $header->setClassName($column->getAlignClass());
                }
                $header->setData($emptyRow);
                $header->render();
            }

            $this->header_row->finishRender();

        }


    }

    protected function renderImpl()
    {

        $v = new ValueInterleave();
        $this->position_index = 0;

        while ($data = $this->iterator->next()) {

            $this->row->setClassName($v->value());

            $this->row->startRender();

            $names = array_keys($this->columns);

            foreach ($names as $pos => $name) {

                $column = $this->getColumn($name);

                $cell = $column->getCellRenderer();
                $header = $column->getHeaderCellRenderer();
                $label = $header->getValue();
                if ($column->getAlignClass()) {
                    $cell->setClassName($column->getAlignClass());
                }

                $cell->setData($data);
                $cell->setAttribute("title", $label);
                $cell->render();
            }

            $this->row->finishRender();

            $this->position_index++;

            $v->advance();
        }

    }

    public function finishRender()
    {
        $this->table->finishRender();

        parent::finishRender();
    }

}

?>