<?php
include_once("lib/beans/DatedPublicationBean.php");
include_once("lib/components/Component.php");
include_once("lib/utils/ValueInterleave.php");

class PublicationArchiveComponent extends Component implements IHeadRenderer {

  protected $bean;
  protected $archive_year;
  protected $archive_month;
  protected $archive_title = false;
  protected $link_page = "";

  protected $have_selection = false;
  
  public function getYear()
  {
	  return $this->archive_year;
  }
  public function getMonth()
  {
	  return $this->archive_month;
  }
  public function haveSelection()
  {
      return $this->have_selection;
  }
  public function __construct(DatedPublicationBean $bean, $link_page)
  {
	  parent::__construct();
	  $this->bean = $bean;
	 
	  $this->processRequest();
	  $this->link_page = $link_page;

// 	  $this->setClassName("panel_list_outer");
  }
  
  public function renderStyle()
  {
      echo "<link rel='stylesheet' href='".SITE_ROOT."lib/css/PublicationArchiveComponent.css' type='text/css' >";
      echo "\n";
  }
  public function renderScript()
  {
    
  }

  protected function processRequest()
  {

	    if (isset($_GET["year"]) && isset($_GET["month"])) {
		$this->archive_year = (int)$_GET["year"];
		$this->archive_month = DBDriver::get()->escapeString($_GET["month"]);
		$this->have_selection = true;
	    }
	    else if (isset($_GET[$this->bean->getPrKey()])) {
		$itemID = (int)$_GET[$this->bean->getPrKey()];
		
		$item_row = $this->bean->getByID($itemID);
		$this->archive_year = date("Y", strtotime($item_row["item_date"]));
		$this->archive_month = date("F", strtotime($item_row["item_date"]));
		
		
	    }
	    else {
		    $arr_years=$this->bean->getYearsArray();
//  print_r($arr_years);			
	

		    $start=strtotime(date("F"));

		    if (count($arr_years)>0){
			    $this->archive_year=$arr_years[0];
			    $this->archive_month=date("F", strtotime("1 December ".$this->archive_year));
		    	

			    $arr = $this->bean->filterMonthList($this->archive_year, $this->archive_month);
				$cc=0;
			    while(count($arr)==0){		
					    $start = strtotime("1 ".$this->archive_month." ".$this->archive_year);
					    $start = strtotime("-1 month",$start);

					    $this->archive_month=date("F",$start);
						
					//echo ($this->archive_month)."<br>";

					    $arr = $this->bean->filterMonthList($this->archive_year, $this->archive_month);
// 			//print_r($arr);
// 			//echo count($arr);
						if ($cc>(count($arr_years)*12))break;
						$cc++;
			    }
		    }
	    }
  }

  public function renderImpl()
  {
		global $left, $right;

		echo "<div class='viewport'>";

		$m = array("January","February","March","April","May","June","July","August","September","October","November","December");	

		$years_array = $this->bean->getYearsArray();

		$v = new ValueInterleave("even", "odd");

		

		$cls = $v->value();

		for ($a=0;$a<count($years_array);$a++){

			$year = $years_array[$a];
			echo "<div class='archive_year $cls' >";
			echo "<a onClick='javascript:toggleArchiveYear($year);'>$year</a>";
			echo "</div>";
			
			echo "<div class='months' year='$year'>";
			
			echo "<div class='list'>";
			$c=0;
			for ($b=0;$b<count($m);$b++) {
			
			  if ($c==0) echo "<div class='column'>";
			  
			      $have_data = $this->bean->containsDataForMonth($year,$m[$b]);
			      
			      $href = "year=$year&month=".$m[$b];
			      if (strpos($this->link_page,"?")>0){
				      $href=$this->link_page."&".$href;
			      }
			      else {
				      $href=$this->link_page."?".$href;
			      }
			      if ($have_data) {
				$active="";
				if (strcmp($m[$b],$this->archive_month)==0)
				{
				  $active="selected";
				}
				echo "<a href='$href' $active>";
				echo tr($m[$b]);
				echo "</a>";
			      }
			      else {
				echo "<span>";
				echo $m[$b];
				echo "</span>";
			      }
			  $c++;
			  if ($c==4) {
			    echo "</div>";
			    $c=0;
			  }
			  
			}

			$v->advance();
			echo "</div>";
			echo "</div>";
		}
		
		echo "</div>";

?>
<script type='text/javascript'>
function toggleArchiveYear(year)
{
  $(".months").css("display", "none");
  
  $(".months[year='"+year+"']").css("display", "block");
  
}
addLoadEvent(function(){
  toggleArchiveYear(<?php echo $this->archive_year;?>);
});
</script>

<?php

	}
  

  
  
}