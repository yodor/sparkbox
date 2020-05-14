<?php
include_once("components/Component.php");
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/renderers/cells/TableHeaderCellRenderer.php");

class TableColumn extends Component
{

    /**
     * @var TableCellRenderer|null
     */
    protected $header;

    /**
     * @var TableCellRenderer|null
     */
    protected $cell;

    /**
     * @var TableView
     */
    protected $view;

    protected $field_name = "";
    protected $label = "";

    protected $sortable = TRUE;

    public function __construct(string $field_name, string $label, TableCellRenderer $cr = NULL, TableCellRenderer $hr = NULL)
    {
        parent::__construct();

        $this->field_name = $field_name;
        $this->label = $label;
        $this->cell = $cr;
        $this->header = $hr;

        if (is_null($hr)) $this->setHeaderCellRenderer(new TableHeaderCellRenderer());

        if (is_null($cr)) $this->setCellRenderer(new TableCellRenderer());

        if (strcasecmp($field_name, "actions") == 0) {
            $this->getHeaderCellRenderer()->setSortable(FALSE);
        }

    }


    public function setHeaderCellRenderer(TableCellRenderer $renderer)
    {
        $this->header = $renderer;
        $this->header->setColumn($this);
    }

    public function getHeaderCellRenderer(): TableCellRenderer
    {
        return $this->header;
    }

    public function getCellRenderer(): TableCellRenderer
    {
        return $this->cell;

    }

    public function setCellRenderer(TableCellRenderer $renderer)
    {
        $this->cell = $renderer;
        $this->cell->setColumn($this);
        $this->sortable = $renderer->isSortable();
    }

    public function getView(): TableView
    {
        return $this->view;
    }

    public function setView(TableView $view)
    {
        $this->view = $view;
    }

    public function getFieldName(): string
    {
        return $this->field_name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isSortable()
    {
        return $this->sortable;
    }

    public function setSortable(bool $mode)
    {
        $this->sortable = $mode;
    }

    public function startRender()
    {

    }

    public function renderImpl()
    {

    }

    public function finishRender()
    {

    }

}

?>