<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/items/NestedSetItemRenderer.php");
include_once("lib/beans/NestedSetBean.php");
include_once("lib/utils/SQLSelect.php");
include_once("lib/utils/IQueryFilter.php");
include_once("lib/utils/ISelectSource.php");

class NestedSetTreeView extends Component implements ISelectSource
{

    public $open_all = true;
    public $list_label = "";

    protected $data_source = NULL;
    protected $select_qry = NULL;

    protected $item_renderer = NULL;

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
        $arr[] = SITE_ROOT . "lib/css/TreeView.css";
        return $arr;
    }

    public function requiredScript()
    {
        $arr = parent::requiredScript();
        $arr[] = SITE_ROOT . "lib/js/TreeView.js";
        return $arr;
    }

    public function setSelectedID($nodeID)
    {
        $this->selected_nodeID = (int)$nodeID;

        $this->selection_path = $this->data_source->constructPath($this->selected_nodeID);
    }

    public function getSelectionPath($nodeID = NULL)
    {
        if ($nodeID) {
            return $this->data_source->constructPath($nodeID);
        }
        else {
            return $this->selection_path;
        }
    }

    public function getSelectedID()
    {
        return $this->selected_nodeID;
    }

    public function getSource()
    {
        return $this->data_source;
    }

    public function getSelectQuery()
    {
        return $this->select_qry;
    }

    public function setSelectQuery(SQLSelect $qry)
    {
        $this->select_qry = $qry;
    }

    public function setItemRenderer(NestedSetItemRenderer $renderer)
    {
        $this->item_renderer = $renderer;
    }

    public function getItemRenderer()
    {
        return $this->item_renderer;
    }

    public function setSource(NestedSetBean $bean)
    {

        $sqry = $bean->listTreeSelect();

        //take first text or char field
        $storage_types = $bean->storageTypes();

        $this->list_label = $bean->key();

        foreach ($storage_types as $field_name => $storage_type) {
            if (strpos($storage_type, "char") !== false || strpos($storage_type, "text") !== false) {
                $this->list_label = $field_name;
                break;
            }
        }

        $this->setAttribute("source", get_class($bean));

        $this->setSelectQuery($sqry);

        $this->data_source = $bean;


    }

    public function renderImpl()
    {

        if (!($this->data_source instanceof NestedSetBean)) throw new Exception("No suitable data_source assigned");
        if (!($this->item_renderer instanceof NestedSetItemRenderer)) throw new Exception("No suitable item_renderer assigned");

        $db = DBDriver::Get();

        $sql = $this->select_qry->getSQL();

        //         echo $sql;

        $res = $db->query($sql);

        $path = array();

        $source_key = $this->data_source->key();

        $open_tags = 0;

        echo "<ul class='NodeChilds'>";

        while ($row = $db->fetch($res)) {

            $lft = $row["lft"];
            $rgt = $row["rgt"];
            $nodeID = $row[$source_key];

            $render_mode = NestedSetItemRenderer::BRANCH_CLOSED;
            if ($this->open_all) {
                $render_mode = NestedSetItemRenderer::BRANCH_OPENED;
            }
            if ($rgt == $lft + 1) {
                $render_mode = NestedSetItemRenderer::BRANCH_LEAF;
            }

            trbean($nodeID, $this->list_label, $row, $this->data_source);

            while (count($path) > 0 && $lft > $path[count($path) - 1]) {
                array_pop($path);

                if ($open_tags >= 1) {
                    echo "</ul>";
                    echo "</li>";
                    $open_tags -= 2;
                }
            }

            $path[] = $rgt;

            $path_len = count($path);

            echo "<li class='NodeOuter'>";
            $open_tags++;

            $selected = ($nodeID == $this->selected_nodeID) ? true : false;

            $item = clone $this->item_renderer;
            $item->setID($nodeID);
            $item->setDataRow($row);
            $item->setBranchType($render_mode);
            $item->setSelected($selected);

            if ($this->list_label) {
                $item_label = $row[$this->list_label];
                if (isset($row["related_count"])) {
                    $item_label .= " (" . $row["related_count"] . ")";
                }
                $item->setLabel($item_label);
            }

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
