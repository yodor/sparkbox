<?php
include_once("components/Component.php");
include_once("components/renderers/cells/TableCellRenderer.php");
include_once("components/renderers/cells/HeaderCellRenderer.php");

class TableColumn
{

    /**
     * @var HeaderCellRenderer
     */
    protected $header;

    /**
     * @var TableCellRenderer
     */
    protected $cell;

    /**
     * @var TableView
     */
    protected $view;

    /**
     * @var string
     */
    protected $field_name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var bool
     */
    protected $sortable = TRUE;

    const ALIGN_LEFT = "left";
    const ALIGN_RIGHT = "right";
    const ALIGN_CENTER = "center";

    protected $alignClass = "";

    public function __construct(string $field_name, string $label, string $alignClass = "")
    {

        $this->field_name = $field_name;
        $this->label = $label;

        $this->setCellRenderer(new TableCellRenderer());
        $this->setHeaderCellRenderer(new HeaderCellRenderer());

        if (strcasecmp($field_name, "actions") == 0) {
            $this->getHeaderCellRenderer()->setSortable(FALSE);
        }

        if ($alignClass) {
            $this->alignClass = $alignClass;
        }
    }

    public function setAlignClass(string $alignClass)
    {
        $this->alignClass = $alignClass;
    }

    public function getAlignClass(): string
    {
        return $this->alignClass;
    }

    public function setHeaderCellRenderer(HeaderCellRenderer $renderer)
    {
        $this->header = $renderer;
        $this->header->setColumn($this);
    }

    public function getHeaderCellRenderer(): HeaderCellRenderer
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
        $this->setSortable($renderer->isSortable());
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

    public function isSortable(): bool
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