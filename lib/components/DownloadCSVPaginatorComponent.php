<?php
include_once("lib/components/Component.php");


class DownloadCSVPaginatorComponent extends Component 
{

  public function __construct()
  {
	  parent::__construct();

	  $this->setStyleAttribute("padding-left","20px");
	  $this->setStyleAttribute("width","100%");
	  $this->setStyleAttribute("text-align","left");

  }

  public  function startRender()
  {
	  $all_attribs = $this->prepareAttributes();
	  echo "<td class=sort_mode_caption nowrap $all_attribs >";
	  
  }

  public  function renderImpl() 
  {

	  echo "<form method=post>";
	  StyledButton::DefaultButton()->drawSubmit("Download as CSV", "download_csv");
	  echo "</form>";
	  
  }  

  public  function finishRender() 
  {
	  echo "</td>";

  }

}

?>