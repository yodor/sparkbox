<?php
include_once("lib/components/Component.php");

class PageResultsPanel extends Component
{
	protected $ipp;
	protected $total_rows;

	public $show_next;
	public $show_prev;

	public $total_pages;
	public $page;

	
	public $max_page = 7;

	public function __construct($total_rows, $ipp, $class='page')
	{
		$this->setClassName("page");
		$this->ipp = $ipp;
		$this->total_rows = $total_rows;


		$total_pages = $this->total_rows / $this->ipp;


		$qry = $_GET;

		$page=0;
		if (isset($_GET["page"]))
		{
			$page=$_GET["page"];
		}

		if ($page>$total_pages)$page=$total_pages-1;
		if ($page<0)$page=0;

		echo " ";

		$max_page = $this->max_page;

		$cstart = $page - (int)($max_page/2);
		$cend = $page + (int)($max_page/2)+1;

		if ($cstart < 2){
			$cstart = 0;
			$cend = $max_page;
		}
		if ($cend>$total_pages){
			$cend = $total_pages;
		}
		$show_next=false;
		if ($cend < $total_pages){
			$this->show_next=true;
		}
		$show_prev=false;
		if ($cstart > 0) {
			$this->show_prev=true;

		}

		$this->page = $page;
		$this->cend = $cend;
		$this->cstart = $cstart;
		$this->total_pages = $total_pages;
	}

	public function startRender() 
	{
		echo "<span>";
	}

	public function finishRender()
	{
		echo "</span>";
	}

	public function drawPrevButton()
	{
		$qry = $_GET;

		if ($this->page>0)
		{
			$qry["page"]=$this->page-1;
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'> << ".tr("Prev")." </a>";
		}
	}
	public function drawNextButton()
	{
		$qry = $_GET;

		if (($this->page+1)<$this->total_pages)
		{
			$qry["page"]=($this->page+1);
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'>".tr("Next")." >> </a>";
		}
	}
	protected function renderImpl()
	{
		$qry = $_GET;

		$a=$this->cstart;


		if ($this->show_prev)
		{
			$qry["page"]=0;
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'> < ".tr("First")." </a>  ";

			$qry["page"]=$a-1;
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'> << ".tr("Prev")." </a> | ";
			
		}


		while ($a<$this->cend)
		{
			$qry["page"]=$a;

			$q=queryString($qry);

			if ($this->page==$a)
			{
				$linkclass = $this->getClassName().'_invert';
				//echo "<a class=pink_link_invert style='padding:5px;' href='?$q'>".($a+1)."</a> | ";
			}
			else
			{
				$linkclass = $this->getClassName();
				//echo "<a class=pink_link style='padding:5px;' href='?$q'>".($a+1)."</a> | ";
			}
			echo "<a class=$linkclass style='padding:5px;' href='$q'>".($a+1)."</a> | ";
			$a++;
		}

		if ($this->show_next)
		{
			$qry["page"]=($this->page+1);
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'>".tr("Next")." >> </a>";
	
			$qry["page"]=(int)($this->total_pages-1);
			$q=queryString($qry);
			echo "<a class='".$this->getClassName()."' style='padding:5px;' href='$q'>".tr("Last")." > </a>";
		}

	}
}