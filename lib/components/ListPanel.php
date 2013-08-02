<?php
include_once("lib/pages/SitePage.php");
include_once("lib/components/Component.php");
include_once("lib/iterators/SQLIterator.php");
// include_once("lib/components/renders/TextItemRenderer.php");

class ListPanel extends Component {

  protected $page;
  protected $list_view;
  protected $sql_iterator;

  public function __construct(SitePage $page, SQLIterator $iterator=NULL)
  {
	  parent::__construct();
	  $this->page = $page;
	  $this->sql_iterator = $iterator;
	  $this->setClassName("panel_list_outer");
	  $this->list_view=$this->createView();
  }
  public function startRender()
  {
	  $all_attr = $this->prepareAttributes();
	  echo "<div $all_attr>";
	  
  }
  public function finishRender()
  {
	  echo "</div>";
  }
  public function renderImpl()
  {
	$this->list_view->render();
  }

  protected function createView()
  {
	$lv = new ListView($this->page, $this->sql_iterator);
// 	$lv->setCaption("List Panel");

	$lv->setClassName("panel_list");
	$lv->enablePaginators(false);
	$lv->items_per_page=3;

	return $lv;
  }

  public function getListView() 
  {
	  return $this->list_view;
  }
  
}