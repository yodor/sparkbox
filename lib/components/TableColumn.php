<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/cells/TableCellRenderer.php");
include_once("lib/components/renderers/cells/TableHeaderCellRenderer.php");

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
            $this->getHeaderCellRenderer()->setSortable(false);
        }

    }


    public function setHeaderCellRenderer(ICellRenderer $renderer)
    {
        $this->header_renderer = $renderer;
        $this->header_renderer->setParent($this);
    }

    public function getHeaderCellRenderer()
    {
        return $this->header_renderer;
    }

    public function getCellRenderer()
    {
        return $this->cell_renderer;

    }

    public function setCellRenderer(ICellRenderer $renderer)
    {
        $this->cell_renderer = $renderer;
        $this->cell_renderer->setParent($this);
    }

    public function getView()
    {
        return $this->view;
    }

    public function setView(TableView $view)
    {
        $this->view = $view;
    }

    public function getFieldName()
    {
        return $this->field_name;
    }

    public function getLabel()
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
