<?php
include_once("components/Component.php");
include_once("components/renderers/IDataIteratorRenderer.php");
include_once("components/renderers/items/DataIteratorItem.php");

include_once("beans/NestedSetBean.php");
include_once("iterators/IDataIterator.php");
include_once("iterators/SQLQuery.php");

class NestedSetTreeView extends Component implements IDataIteratorRenderer
{

    const string ATTRIBUTE_BRANCH_TYPE = "branch_type";

    const string BRANCH_OPENED = "opened";
    const string BRANCH_CLOSED = "closed";
    const string BRANCH_LEAF = "leaf";

    const int MODE_BRANCHES_FOLDED = 1;
    const int MODE_BRANCHES_UNFOLDED = 2;

    protected int $branch_render_mode = NestedSetTreeView::MODE_BRANCHES_FOLDED;

    /**
     * @var IDataIterator
     */
    protected ?SQLQuery $iterator = NULL;

    /**
     * @var DataIteratorItem
     */
    protected ?DataIteratorItem $item = NULL;

    protected int $selected_nodeID = -1;

    /**
     * Available after rendering, empty if caching is enabled
     * @var array Holds the IDs of the selected nodes and the label from the iterator
     *
     */
    protected array $selection_path = array();

    /**
     * @var array Holds the IDs of the checked nodes
     */
    protected array $checked_nodes = array();

    public function __construct()
    {
        parent::__construct(false);
        $this->setComponentClass("TreeView");
        $this->setTagName("nav");
        $this->setAttribute("itemscope");
        $this->setAttribute("itemtype", "https://schema.org/SiteNavigationElement");

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

    public function setIterator(IDataIterator $query): void
    {
        if (!$query instanceof SQLQuery) throw new Exception("Incorrect iterator");
        $this->iterator = $query;
        $this->setAttribute("source", $this->iterator->name());
    }

    public function getIterator(): IDataIterator
    {
        return $this->iterator;
    }

    /**
     * Set the checked nodes ID
     * @param ...$selection array of IDs
     * @return void
     */
    public function setCheckedNodes(...$selection) : void
    {
        $this->checked_nodes = $selection;
    }

    /**
     * Return the checked nodes ID array
     * @return array
     */
    public function getCheckedNodes() : array
    {
        return $this->checked_nodes;
    }

    public function setSelectedID(int $nodeID) : void
    {
        $this->selected_nodeID = $nodeID;
    }

    /**
     * Available after rendering and selected_nodeID is > -1
     * empty if caching is enabled
     * ID=>LABEL
     * @return array
     */
    public function getSelectionPath() : array
    {
        return $this->selection_path;
    }

    public function getSelectedID() : int
    {
        return $this->selected_nodeID;
    }

    public function setItemRenderer(DataIteratorItem $item): void
    {
        $this->item = $item;
    }

    public function getItemRenderer(): DataIteratorItem
    {
        return $this->item;
    }

    public function setBranchRenderMode(int $mode) : void
    {
        $this->branch_render_mode = $mode;
    }

    public function getBranchRenderMode() : int
    {
        return $this->branch_render_mode;
    }

    public function getCacheName() : string
    {
        $url = URL::Current();
        $url->setClearPageParams(true);
        $result = basename($url->toString())."-".get_class($this)."-".$this->getName();
        if ($this->iterator instanceof SQLQuery) {
            $result.="-".$this->iterator->select->getSQL();
        }
        return $result;
    }

    protected function renderImpl(): void
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
            $item->setAttribute(NestedSetTreeView::ATTRIBUTE_BRANCH_TYPE, $branch_type);
            //move selection to javascript
            //$item->setSelected($selected);
            $item->setChecked(false);

            $item->setChecked(in_array($nodeID, $this->checked_nodes));


            $item_label = $item->getLabel();

            $item->setLabel($item_label);
            $item->render();

            echo "<ul class='NodeChilds'>";
            $open_tags++;
        }

        echo "</ul>";

    }

    //do not cache script code
    public function render(): void
    {
        parent::render();
        ?>
        <script type='text/javascript'>
            onPageLoad(function () {
                let tree_view = new TreeView();
                tree_view.setName("<?php echo $this->getName(); ?>");
                tree_view.initialize();
                tree_view.setSelectedID(<?php echo $this->getSelectedID();?>);
            });
        </script>
        <?php
    }
}
