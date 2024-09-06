<?php
include_once("components/NestedSetTreeView.php");

class NestedSetFilterProcessor
{

    protected $item_clicked_action = NULL;

    protected $bean = NULL;

    public function __construct(NestedSetBean $bean)
    {
        $this->bean = $bean;
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

        $key = $view->getIterator()->key();

        if (isset($_GET[$key])) {
            $nodeID = (int)$_GET[$key];
            $view->setSelectedID($nodeID);
        }

    }

    protected function createDefaultAction(IDataIterator $iterator)
    {
        $tv_item_clicked = new Action("TextItemClicked", "", array(new DataParameter($iterator->key())));

        //clicking on this will clear the page parameter of the paginator from the href url
        $tv_item_clicked->getURL()->setClearPageParams(TRUE);

        return $tv_item_clicked;
    }

    protected function processTextAction(NestedSetTreeView $view)
    {
        $key = $view->getIterator()->key();

        if (!$this->item_clicked_action) {
            $this->item_clicked_action = $this->createDefaultAction($view->getIterator());
        }

        $view->getItemRenderer()->setTextAction($this->item_clicked_action);
    }

}

?>
