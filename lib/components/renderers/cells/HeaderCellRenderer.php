<?php
include_once("components/renderers/cells/TableCellRenderer.php");

class HeaderCellRenderer extends TableCellRenderer implements IGETConsumer
{

    protected $tagName = "TH";
    protected string $sortField = "";
    protected ?URLBuilder $sortLink = NULL;

    const KEY_ORDER_BY = "orderby";
    const KEY_ORDER_DIR = "orderdir";

    public function __construct()
    {
        parent::__construct();
        $this->translation_enabled = true;
        $this->sortLink = new URLBuilder();
        $this->sortLink->buildFrom(SparkPage::Instance()->getURL()->url());
        $this->sortLink->add(new URLParameter(HeaderCellRenderer::KEY_ORDER_BY));
        $this->sortLink->add(new URLParameter(HeaderCellRenderer::KEY_ORDER_DIR, "ASC"));

    }

    public function getSortURL(): ?URLBuilder
    {
        return $this->sortLink;
    }

    public function setDefaultOrderDirection(string $order_dir)
    {
        $this->sortLink->get(self::KEY_ORDER_DIR)->setValue($order_dir);
    }

    public function isSortable(): bool
    {
        return $this->column->isSortable();
    }

    public function setSortable(bool $mode)
    {
        $this->column->setSortable($mode);
    }

    public function setSortField(string $field)
    {
        $this->sortField = $field;
    }

    protected function renderImpl()
    {
        if ($this->column->isSortable()) {

            //default order by field name asc
            echo "<a href='{$this->sortLink->url()}'>";
            parent::renderImpl();
            echo "</a>";

            //current page is using sort by 'this' column
            if (strcmp_isset(self::KEY_ORDER_BY, $this->sortField, $_GET)) {
                //show the arrow link to change order direction
                if (strcmp_isset(self::KEY_ORDER_DIR, "ASC", $_GET)) {
                    //current list is ordered ASC show up arrow and href with opposite direction
                    $this->sortLink->get(self::KEY_ORDER_DIR)->setValue("DESC");
                    echo "<a class='direction' direction='ASC' href='{$this->sortLink->url()}'></a>";
                }
                else {
                    $this->sortLink->get(self::KEY_ORDER_DIR)->setValue("ASC");
                    echo "<a class='direction' direction='DESC' href='{$this->sortLink->url()}'></a>";
                }

            }

        }
        else {
            parent::renderImpl();
        }
    }

    public function setData(array &$data)
    {

        parent::setData($data);

        $this->value = $this->column->getLabel();

        if ($this->isSortable()) {

            if (!$this->sortField) {
                $this->sortField = $this->column->getFieldName();
            }

            $this->sortLink->get(HeaderCellRenderer::KEY_ORDER_BY)->setValue($this->sortField);

        }

    }

    /**
     * @return array The parameter names this object is interacting with
     */
    public function getParameterNames(): array
    {
        return array(HeaderCellRenderer::KEY_ORDER_BY, HeaderCellRenderer::KEY_ORDER_DIR);
    }
}

?>
