<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");
include_once("components/renderers/items/DataIteratorItem.php");

include_once("beans/NestedSetBean.php");
include_once("iterators/IDataIterator.php");


class NestedSetTreeView extends Component implements IDataIteratorRenderer
{

    const BRANCH_OPENED = "opened";
    const BRANCH_CLOSED = "closed";
    const BRANCH_LEAF = "leaf";

    const MODE_BRANCHES_FOLDED = 1;
    const MODE_BRANCHES_UNFOLDED = 2;

    protected $branch_render_mode = NestedSetTreeView::MODE_BRANCHES_FOLDED;

    /**
     * @var IDataIterator
     */
    protected $iterator = NULL;

    /**
     * @var DataIteratorItem
     */
    protected $item = NULL;

    protected $selected_nodeID = -1;
    protected $selection_path = array();

    /**
     * @var array Holds the IDs of the checked nodes
     */
    protected $checked_nodes = array();

    public function __construct()
    {
        parent::__construct();
        $this->setComponentClass("TreeView");
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/TreeView.css";
        return $arr;
    }

    public function requiredScript() : array
    {
        $arr = parent::requiredScript();
        $arr[] = SPARK_LOCAL . "/js/TreeView.js";
        return $arr;
    }

    public function setIterator(IDataIterator $query)
    {
        $this->iterator = $query;
        $this->setAttribute("source", $this->iterator->name());
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    public function setSelectedID(int $nodeID)
    {
        $this->selected_nodeID = (int)$nodeID;
    }

    public function setCheckedNodes(...$selection)
    {
        $this->checked_nodes = $selection;
    }

    /**
     * Available after rendering and selected_nodeID is > -1
     * @return array
     */
    public function getSelectionPath() : array
    {
        return $this->selection_path;
    }

    public function getSelectedID() : int
    {
        return (int)$this->selected_nodeID;
    }

    public function setItemRenderer(DataIteratorItem $renderer)
    {
        $this->item = $renderer;
    }

    public function getItemRenderer(): DataIteratorItem
    {
        return $this->item;
    }

    public function setBranchRenderMode(int $mode)
    {
        $this->branch_render_mode = $mode;
    }

    public function getBranchRenderMode() : int
    {
        return $this->branch_render_mode;
    }

    protected function renderImpl()
    {

        $path = array();

        $source_key = $this->iterator->key();

        $open_tags = 0;

        if ($this->iterator instanceof SQLQuery) {
            $this->iterator->select->setMode(SQLSelect::SQL_CACHE);
        }

        $num = $this->iterator->exec();

        echo "<ul class='NodeChilds'>";

        $selection = array();

        while ($row = $this->iterator->next()) {

            if (!isset($row["lft"]) || !isset($row["rgt"])) throw new Exception("No suitable iterator set (lft/rgt) missing");
            $lft = $row["lft"];
            $rgt = $row["rgt"];

            $nodeID = $row[$source_key];

            $branch_type = NestedSetTreeView::BRANCH_CLOSED;
            if ($this->branch_render_mode == NestedSetTreeView::MODE_BRANCHES_UNFOLDED) {
                $branch_type = NestedSetTreeView::BRANCH_OPENED;
            }
            if ($rgt == $lft + 1) {
                $branch_type = NestedSetTreeView::BRANCH_LEAF;
            }

            trbean($nodeID, $this->item->getLabelKey(), $row, $this->iterator->name());

            while (count($path) > 0 && $lft > $path[count($path) - 1]) {
                array_pop($path);
                array_pop($selection);

                if ($open_tags >= 1) {
                    echo "</ul>";
                    echo "</li>";
                    $open_tags -= 2;
                }
            }

            $path[] = $rgt;

            $selection[] = $nodeID;

            $selected = FALSE;
            if ($nodeID == $this->selected_nodeID) {
                $this->selection_path = $selection;
                $selected = TRUE;
            }

            echo "<li class='NodeOuter'>";
            $open_tags++;

            $item = $this->item;
            $item->setID($nodeID);
            $item->setData($row);
            $item->setAttribute("branch_type", $branch_type);
            $item->setSelected($selected);
            if (is_array($this->checked_nodes)) {
                $item->setChecked(in_array($nodeID, $this->checked_nodes));
            }

            $item_label = $item->getLabel();

            $item->setLabel($item_label);
            $item->render();

            echo "<ul class='NodeChilds'>";
            $open_tags++;
        }

        echo "</ul>";

    }

    public function finishRender()
    {
        parent::finishRender();

        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                var tree_view = new TreeView();
                tree_view.setName("<?php echo $this->getName(); ?>");
                tree_view.initialize();
            });
        </script>
        <?php
    }

}
