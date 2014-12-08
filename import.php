<?php
include_once("session.php");
include_once("lib/utils/CSVTemplateLoader.php");
include_once("class/pages/DemoPage.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/BrandsBean.php");
include_once("class/beans/ProductCategoriesBean.php");


class ImportIvona extends CSVTemplateLoader
{
	const PRODUCT_CODE = 0;
	const BRAND_NAME = 1;
	const CATEGORY_NAME = 2;
	const CATEGORY_BRANCH = 3;
	const VISIBLE = 4;
	const PRODUCT_NAME = 5;
	const PRODUCT_SUMMARY = 6;
	const PRODUCT_DESCRIPTION = 8;
	const PRODUCT_SELL_PRICE = 10;
	const PRODUCT_BUY_PRICE = 11;
	const PRODUCT_WEIGHT = 12;
	const PRODUCT_STOCK_AMOUNT = 13;
	protected $prods = NULL;
	protected $brands = NULL;
	protected $pc = NULL;
	protected $db = NULL;
	
	public function __construct($zipfile)
	{
		parent::__construct($zipfile);
		$this->prods = new ProductsBean();
		$this->brands = new BrandsBean();
		$this->pc = new ProductCategoriesBean();
		$this->db = DBDriver::factory();
		$this->pc->setDB($this->db);
		$this->brands->setDB($this->db);
		$this->prods->setDB($this->db);
	}
	public function processKeysRow($row)
	{
		$this->keys_row = $row;
		
		echo "<tr>";
		foreach($row as $key=>$val) {
			echo "<th>$val</th>";
		}
		echo "</tr>";
	}
	//dummy output only function. actual functionality reimplemented in a subclass
	public function processDataRow($row)
	{
		
		
		$prod_row = array("product_code"=>$row[ImportIvona::PRODUCT_CODE],
						  "visible"=>$row[ImportIvona::VISIBLE],
						  "product_name"=>$row[ImportIvona::PRODUCT_NAME],
						  "product_summary"=>$row[ImportIvona::PRODUCT_SUMMARY],
						  "product_description"=>$row[ImportIvona::PRODUCT_DESCRIPTION],
						  "sell_price"=>$row[ImportIvona::PRODUCT_SELL_PRICE],
						  "buy_price"=>$row[ImportIvona::PRODUCT_BUY_PRICE],
						  "weight"=>$row[ImportIvona::PRODUCT_WEIGHT],
						  "stock_amount"=>$row[ImportIvona::PRODUCT_STOCK_AMOUNT]);

		foreach($prod_row as $key=>$val) {
		  $prod_row[$key]=$this->db->escapeString($val);
		}

		
		$brand_name = $this->db->escapeString($row[ImportIvona::BRAND_NAME]);
		if (strlen($brand_name)<1)$brand_name="No Name";
		$brandID=-1;
		$num_rows = $this->brands->startIterator("WHERE brand_name='$brand_name' LIMIT 1");
		if ($num_rows<1) {
		  $brand_row = array("brand_name"=>$brand_name);
		  $brandID = $this->brands->insertRecord($brand_row);
		}
		if ($this->brands->fetchNext($brand_row)) {
		  $brandID=$brand_row["brandID"];
		}
		$prod_row["brandID"]=$brandID;
		$prod_row["brand_name"]=$brand_name;
		
		$category_name = $this->db->escapeString($row[ImportIvona::CATEGORY_NAME]);
		$branch = explode("|", $row[ImportIvona::CATEGORY_BRANCH]);
		$category_branch = array();
		
		foreach($branch as $key=>$val) {
			$branch_name = $this->db->escapeString(trim($val));
			if (strlen($branch_name)<1)continue;
			$category_branch[] = $branch_name;
		}
		$catID = -1;
		
		
		try {
		  $this->db->transaction();
		  
		  for ($a=0;$a<count($category_branch);$a++)
		  {
			  $cat_name = $category_branch[$a];
			  
			  if ($a<1) {
				$num = $this->pc->startIterator("WHERE category_name LIKE '%$cat_name%' AND parentID='0' LIMIT 1");
				if ($num<1) {
				  $cat_row = array("category_name"=>$cat_name, "parentID"=>0);
				  $catID = $this->pc->insertRecord($cat_row, $this->db);
				  if ($catID<1)throw new Exception("Unable to insert category at level 0: ".$this->db->getError());
				}
				else {
				  if ($this->pc->fetchNext($cat_row)) {
					$catID = $cat_row["catID"];
				  }
				}
			  }
			  else {
				$num = $this->pc->startIterator("WHERE category_name LIKE '%$cat_name%' AND parentID='$catID' LIMIT 1");
				if ($num<1) {
				  $cat_row = array("category_name"=>$cat_name,"parentID"=>$catID);
				  $catID = $this->pc->insertRecord($cat_row, $this->db);
				  if ($catID<1)throw new Exception("Unable to insert category at level $a. ".$this->db->getError());
				}
				else {
				  if ($this->pc->fetchNext($cat_row)) {
					$catID = $cat_row["catID"];
				  }
				}
			  }
		  }
		
		  $prod_row["catID"]=$catID;
		  $prod_row["prodID"]=(int)$prod_row["product_code"];
		  $prodID = $this->prods->insertRecord($prod_row, $this->db);
		  if ($prodID<1) throw new Exception("Unable to insert product: ".$this->db->getError());
		  $this->db->commit();
		  echo "<HR>";
		  
		  echo "Inserted prodID: $prodID";
		}
		catch (Exception $e) {
		  $this->db->rollback();
		  echo "<HR>";
		  echo $e->getMessage();
		  var_dump($category_branch);
		  echo "<HR>";
		}
		
	}
}

$page = new DemoPage();

$page->beginPage();

$csv = new ImportIvona("export_ivo.csv.zip");
$csv->processFile("export_ivo.csv");


$page->finishPage();
?>
