<?php
include_once("components/Component.php");
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/renderers/cells/TableHeaderCellRenderer.php");

class TableColumn extends Component
{

    protected $header_renderer;
    protected $cell_renderer;

    protected $view;

    protected $field_name;
    protected $label;


    public function __construct(string $field_name, string $label, ICellRenderer $cr = NULL, ICellRenderer $hr = NULL)
    {
        parent::__construct();

        $this->field_name = $field_name;
        $this->label = $label;
        $this->cell_renderer = $cr;
        $this->header_renderer = $hr;

        if (is_null($hr)) $this->setHeaderCellRenderer(new TableHeaderCellRenderer());

        if (is_null($cr)) $this->setCellRenderer(new TableCellRenderer());

        if (strcasecmp($field_name, "actions") == 0) {
            $this->getHeaderCellRenderer()->setSortable(FALSE);
        }

    }


    public function setHeaderCellRenderer(ICellRenderer $renderer)
    {
        $this->header_renderer = $renderer;
        $this->header_renderer->setParent($this);
    }

    public function getHeaderCellRenderer(): ICellRenderer
    {
        return $this->header_renderer;
    }

    public function getCellRenderer(): ICellRenderer
    {
        return $this->cell_renderer;

    }

    public function setCellRenderer(ICellRenderer $renderer)
    {
        $this->cell_renderer = $renderer;
        $this->cell_renderer->setParent($this);
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