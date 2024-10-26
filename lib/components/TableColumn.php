<?php
include_once("objects/SparkObject.php");
include_once("components/renderers/cells/TableCell.php");
include_once("components/renderers/cells/HeaderCell.php");

class TableColumn extends SparkObject
{

    /**
     * @var HeaderCell
     */
    protected HeaderCell $header;

    /**
     * @var TableCell
     */
    protected TableCell $cell;

    /**
     * @var TableView
     */
    protected TableView $view;

    /**
     * @var string
     */
    protected string $label;

    /**
     * @var bool
     */
    protected bool $sortable = TRUE;

    const string ALIGN_LEFT = "left";
    const string ALIGN_RIGHT = "right";
    const string ALIGN_CENTER = "center";

    protected string $alignClass = "";

    public function __construct(string $column, string $label, string $alignClass = "")
    {
        parent::__construct();

        $this->name = $column;
        $this->label = $label;

        $this->setCellRenderer(new TableCell());
        $this->setHeaderCellRenderer(new HeaderCell());

        if (strcasecmp($column, "actions") == 0) {
            $this->sortable = false;
        }

        if ($alignClass) {
            $this->alignClass = $alignClass;
        }
    }

    public function setAlignClass(string $alignClass): void
    {
        $this->alignClass = $alignClass;
    }

    public function getAlignClass(): string
    {
        return $this->alignClass;
    }

    public function setHeaderCellRenderer(HeaderCell $renderer): void
    {
        $this->header = $renderer;
        $this->header->setColumn($this);
    }

    public function getHeaderCellRenderer(): HeaderCell
    {
        return $this->header;
    }

    public function setCellRenderer(TableCell $renderer): void
    {
        $this->cell = $renderer;
        $this->cell->setColumn($this);

    }
    public function getCellRenderer(): TableCell
    {
        return $this->cell;

    }

    public function getView(): TableView
    {
        return $this->view;
    }

    public function setView(TableView $view): void
    {
        $this->view = $view;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $mode): void
    {
        $this->sortable = $mode;
    }

}

?>