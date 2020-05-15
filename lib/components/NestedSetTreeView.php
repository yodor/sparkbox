<?php
include_once("components/Component.php");
include_once("components/renderers/items/DataIteratorItem.php");

include_once("beans/NestedSetBean.php");
include_once("iterators/IDataIterator.php");

//TODO: extend AbstractResultView?
class NestedSetTreeView extends Component implements IDataIteratorRenderer
{

    const BRANCH_OPENED = "opened";
    const BRANCH_CLOSED = "closed";
    const BRANCH_LEAF = "leaf";

    //    const ICON_HANDLE_OPEN = "+";
    //    const ICON_HANDLE_CLOSE = "-";
    //    const ICON_HANDLE_LEAF = "&middot";

    public $open_all = FALSE;

    //    protected $data_source = NULL;
    //    protected $select_qry = NULL;

    /**
     * @var IDataIterator
     */
    public $iterator = NULL;

    /**
     * @var DataIteratorItem
     */
    protected $item = NULL;

    protected $selected_nodeID = NULL;
    protected $selection_path = array();

    public function __construct()
    {
        parent::__construct();
        $this->setClassName("TreeView");
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/TreeView.css";
        return $arr;
    }

    public function requiredScript()
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

    public function setSelectedID($nodeID)
    {
        $this->selected_nodeID = (int)$nodeID;

        //        $this->selection_path = $this->data_source->constructPath($this->selected_nodeID);
    }

    /**
     * Available after rendering and selected_nodeID is > -1
     * @return array
     */
    public function getSelectionPath()
    {

        return $this->selection_path;

    }

    public function getSelectedID()
    {
        return $this->selected_nodeID;
    }

    public function setItemRenderer(DataIteratorItem $renderer)
    {
        $this->item = $renderer;
    }

    public function getItemRenderer(): DataIteratorItem
    {
        return $this->item;
    }

    protected function renderImpl()
    {

        $path = array();

        $source_key = $this->iterator->key();

        $open_tags = 0;

        //        echo $this->iterator->select->getSQL();

        $num = $this->iterator->exec();

        echo "<ul class='NodeChilds'>";

        $selection = array();

        while ($row = $this->iterator->next()) {

            if (!isset($row["lft"]) || !isset($row["rgt"])) throw new Exception("No suitable iterator set (lft/rgt) missing");
            $lft = $row["lft"];
            $rgt = $row["rgt"];

            $nodeID = $row[$source_key];

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

            $render_mode = NestedSetTreeView::BRANCH_CLOSED;
            if ($this->open_all) {
                $render_mode = NestedSetTreeView::BRANCH_OPENED;
            }
            if ($rgt == $lft + 1) {
                $render_mode = NestedSetTreeView::BRANCH_LEAF;
            }

            $item = clone $this->item;
            $item->setID($nodeID);
            $item->setData($row);

            $item->setAttribute("branch_type", $render_mode);

            $item->setSelected($selected);

            $item_label = $item->getLabel();

            if (isset($row["related_count"])) {
                $item_label .= " (" . $row["related_count"] . ")";
            }

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
                tree_view.attachWith("<?php echo $this->getName(); ?>");
            });
        </script>
        <?php
    }

}
