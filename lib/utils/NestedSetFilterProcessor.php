<?php
include_once("components/NestedSetTreeView2.php");

class NestedSetFilterProcessor
{

    protected $item_clicked_action = NULL;

    public function __construct()
    {
        $this->item_clicked_action = NULL;
    }

    public function setItemClickedAction(Action $action)
    {
        $this->item_clicked_action = $action;
    }

    public function process(NestedSetTreeView $view)
    {

        $this->processGetVars($view);
        $this->processTextAction($view);

    }

    protected function processGetVars(NestedSetTreeView $view)
    {

        $bean = $view->getSource();
        $source_prkey = $bean->key();

        if (isset($_GET[$source_prkey])) {
            $nodeID = (int)$_GET[$source_prkey];
            $view->setSelectedID($nodeID);
        }

    }

    protected function createDefaultAction($bean)
    {
        $tv_item_clicked = new Action("TextItemClicked", "", array(new ActionParameter($bean->key(), $bean->key())));

        //clicking on this will clear the page parameter of the paginator from the href url
        $tv_item_clicked->setClearPageParam(true);

        return $tv_item_clicked;
    }

    protected function processTextAction(NestedSetTreeView $view)
    {
        $bean = $view->getSource();
        $source_prkey = $bean->key();

        if (!$this->item_clicked_action) {
            $this->item_clicked_action = $this->createDefaultAction($bean);
        }

        $view->getItemRenderer()->setTextAction($this->item_clicked_action);
    }


}

?>
