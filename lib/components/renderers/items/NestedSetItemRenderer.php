<?php
include_once("lib/components/Component.php");
include_once("lib/components/renderers/INestedSetSourceItem.php");


abstract class NestedSetItemRenderer extends Component implements INestedSetSourceItem
{

    const BRANCH_OPENED = "opened";
    const BRANCH_CLOSED = "closed";
    const BRANCH_LEAF = "leaf";

    const ICON_HANDLE_OPEN = "+";
    const ICON_HANDLE_CLOSE = "-";
    const ICON_HANDLE_LEAF = "&middot";


    protected $data_row = array();
    protected $id = -1;
    protected $selected = false;
    protected $label = "";
    protected $branch_type = NestedSetItemRenderer::BRANCH_LEAF;


    public function setDataRow($data_row)
    {
        $this->data_row = $data_row;
    }

    public function getDataRow()
    {
        return $this->data_row;
    }

    public function setID($id)
    {
        $this->setAttribute("nodeID", $id);

        $this->id = $id;
    }

    public function getID()
    {

        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setSelected($mode)
    {
        $this->selected = $mode;

        $this->setAttribute("active", (($mode) ? 1 : 0));

    }

    public function isSelected()
    {
        return $this->selected;
    }


    public function __construct()
    {
        parent::__construct();
        $this->setClassName("Node");

    }

    public function setBranchType($mode)
    {
        $this->setAttribute("branch_type", $mode);

        $this->branch_type = $mode;
    }

    public function getBranchType()
    {
        return $this->branch_type;
    }


}