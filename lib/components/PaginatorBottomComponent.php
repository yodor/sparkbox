<?php
include_once("lib/components/PaginatorComponent.php");

class PaginatorBottomComponent extends PaginatorComponent
{

	public function __construct(Paginator $paginator)
	{
		parent::__construct($paginator);

		$this->setClassName("PaginatorBottomComponent");
	}

	public function renderImpl()
	{
		
		echo "<div class='cell page_info'>";

		  echo tr("Page");
		  echo ": ";
		  $page = 0;
		  if ($this->paginator->getPagesTotal()>0) {
		      $page = $this->paginator->getCurrentPage()+1;
		  }
		  echo $page;
		  echo "&nbsp;";
		  echo tr("of");
		  echo "&nbsp;";
		  echo $this->paginator->getPagesTotal();

		echo "</div>";

		echo "<div class='cell page_navigation long'>";
		  if ($this->paginator->getPagesTotal()>1) {
			$this->renderPageSelector();
		  }
		echo "</div>";

		echo "<div class='cell results_info'>";
		echo tr("Results").": ".$this->paginator->getResultsTotal();
		echo "</div>";
  
		
	}
}

?>
