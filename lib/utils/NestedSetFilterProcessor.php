<?php
include_once("lib/components/NestedSetTreeView2.php");

class NestedSetFilterProcessor
{
	protected $view = NULL;
	
	public function __construct()
	{
		$this->view = NULL;
	}

	public function process(NestedSetTreeView $view)
	{
		$this->view = $view;
		
		$this->processGetVars();
		$this->processTextAction();
		
	}
	protected function processGetVars()
	{
		
		$bean = $this->view->getSource();
		$source_prkey = $bean->getPrKey();

		if (isset($_GET[$source_prkey])) {
		  $nodeID = (int)$_GET[$source_prkey];
		  $this->view->setSelectedID($nodeID);
		}
		  
	}

	protected function processTextAction()
	{
		$bean = $this->view->getSource();
		$source_prkey = $bean->getPrKey();
		
		$tv_item_clicked = new Action(
		  "TextItemClicked", "?filter=self",
		  array(
			new ActionParameter($bean->getPrKey(), $bean->getPrKey())
		  )
		);

		$this->view->getItemRenderer()->setTextAction($tv_item_clicked);
	}
	

}
?>