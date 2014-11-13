<?php
include_once("lib/components/Component.php");
include_once("lib/forms/KeywordSearchForm.php");
include_once("lib/utils/IQueryFilter.php");
include_once("lib/forms/renderers/FormRenderer.php");

class KeywordSearchComponent extends Component implements IHeadRenderer, IQueryFilter
{

	protected $sform = false;

	public $form_append = "";
	public $form_prepend = "";
	
	protected $buttons = array();
	
	const ACTION_SEARCH = "search";
	const ACTION_CLEAR = "clear";
	
	
	public function getButton($action)
	{
	  return $this->buttons[$action];
	  
	}
	public function __construct($table_fields)
	{
	  parent::__construct();

	  $this->sform = new KeywordSearchForm($table_fields);
	  
	  $qry = $_REQUEST;
	  
	  if (strcmp_isset("clear", "search", $qry) === true) {

	      $this->sform->clearQuery($qry);
	      $qstr = queryString($qry);
	      $loc = $_SERVER["PHP_SELF"]."$qstr";

	      header("Location: $loc");
	      exit;
	  }
	  $this->sform->loadPostData($_REQUEST);
	  $this->sform->validate();
	  
	  
	  $submit_search = StyledButton::DefaultButton();
	  $submit_search->setType(StyledButton::TYPE_SUBMIT);
	  $submit_search->setText("Search");
	  $submit_search->setName("filter");
	  $submit_search->setValue("search");
	  $submit_search->setAttribute("action", "search");
	  $this->buttons[KeywordSearchComponent::ACTION_SEARCH] = $submit_search;
	  
	  $submit_clear = StyledButton::DefaultButton();
	  $submit_clear->setType(StyledButton::TYPE_SUBMIT);
	  $submit_clear->setText("Clear");
	  $submit_clear->setName("clear");
	  $submit_clear->setValue("search");
	  $submit_clear->setAttribute("action", "clear");
	  $this->buttons[KeywordSearchComponent::ACTION_CLEAR] = $submit_clear;
	  
	  $this->sform->setRenderer(new FormRenderer());
	  
	}
	public function renderScript()
	{}
	public function renderStyle()
	{
	    echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/InputRenderer.css' type='text/css' >";
	    echo "\n";
	    echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/KeywordSearchComponent.css' type='text/css' >";
	    echo "\n";
	}
	public function startRender()
	{
		parent::startRender();
		$this->sform->getRenderer()->startRender();
		
		echo $this->form_prepend;
	}
	public function finishRender()
	{
		echo $this->form_append;
		$this->sform->getRenderer()->finishRender();
		
		parent::finishRender();
	}
	public function getForm()
	{
		return $this->sform;
	}
	public function renderImpl()
	{
		echo "<div class='fields'>";

		$field = $this->sform->getField("keyword");
		$field->getLabelRenderer()->renderLabel($field);

		$field->getRenderer()->renderField($field);

		echo "</div>";
		
		echo "<div class='buttons'>";
		
		$submit_search = $this->buttons[KeywordSearchComponent::ACTION_SEARCH];
		$submit_search->render();
		
		$submit_clear = $this->buttons[KeywordSearchComponent::ACTION_CLEAR];
		$submit_clear->render();
		
		
		echo "</div>";

		
	}
	public function processSearch(SelectQuery& $select_query)
	{
	      $search_query = $this->sform->searchFilterQuery();

	      $select_query = $select_query->combineWith($search_query);

	}
	public function processSearchHaving(SelectQuery& $select_query)
	{
	      $search_query = $this->sform->searchFilterQuery();


	      $select_query->having = $search_query->where;

	}
	public function getQueryFilter()
	{
	    return $this->sform->searchFilterQuery();
	}

}