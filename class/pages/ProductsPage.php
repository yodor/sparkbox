<?php
include_once("class/pages/DemoPage.php");


class ProductsPage extends DemoPage
{

	public $derived_table = NULL;
	public $derived = NULL;
	
    public function __construct()
    {
		parent::__construct();
		
		$derived = new SelectQuery();
		$derived->fields = "
		iav.value as ia_value, ca.attribute_name as ia_name,
		(pclrs.color_photo IS NOT NULL) as have_chip, pc.catID, pc.category_name, 
(SELECT GROUP_CONCAT(DISTINCT(pi1.size_value) SEPARATOR '|') FROM product_inventory pi1 WHERE pi1.prodID=pi.prodID AND (pi1.pclrID = pi.pclrID OR pi.pclrID IS NULL) GROUP BY pi.pclrID ) as size_values, 
(SELECT GROUP_CONCAT(DISTINCT(pi2.color) SEPARATOR '|') FROM product_inventory pi2 WHERE pi2.prodID=pi.prodID ORDER BY pclrID ASC ) as colors, 
(SELECT GROUP_CONCAT(DISTINCT(pi3.pclrID) SEPARATOR '|') FROM product_inventory pi3 WHERE pi3.prodID=pi.prodID ORDER BY pclrID ASC ) as color_ids, 

(SELECT GROUP_CONCAT(DISTINCT(CONCAT(ca.attribute_name,':', cast(iav.value as char))) SEPARATOR '|') FROM inventory_attribute_values iav JOIN class_attributes ca ON ca.caID = iav.caID WHERE iav.piID = pi.piID) as inventory_attributes, 

(SELECT GROUP_CONCAT(DISTINCT(CONCAT(ca.attribute_name,':', cast(iav.value as char))) SEPARATOR '|') FROM product_inventory pi4 JOIN inventory_attribute_values iav ON iav.piID=pi4.piID JOIN class_attributes ca ON ca.caID = iav.caID WHERE pi4.prodID=pi.prodID AND pi4.pclrID=pi.pclrID) as inventory_attributes_all, 

(SELECT ppID FROM product_photos pp WHERE pp.prodID=pi.prodID ORDER BY position ASC LIMIT 1) as ppID,
(SELECT pclrpID FROM product_color_photos pcp WHERE pcp.pclrID=pi.pclrID ORDER BY position ASC LIMIT 1) as pclrpID,

pi.price - (pi.price * (coalesce(sp.discount_percent,0)) / 100.0) AS sell_price, 
pi.piID, pi.size_value, pi.color, pi.pclrID, p.brand_name, pi.prodID, 
p.product_code, p.product_name, p.product_description, p.keywords 
";
		$derived->from = " product_inventory pi 

JOIN products p ON (p.prodID = pi.prodID AND p.visible=1) 
JOIN product_categories pc ON pc.catID=p.catID 
LEFT JOIN store_promos sp ON (sp.targetID = p.catID AND sp.target='Category' AND sp.start_date <= NOW() AND sp.end_date >= NOW()) 
LEFT JOIN product_colors pclrs ON pclrs.pclrID = pi.pclrID
LEFT JOIN inventory_attribute_values iav ON iav.piID=pi.piID LEFT JOIN class_attributes ca ON ca.caID=iav.caID
";
		$derived->where = "";
		
// 		echo $derived->getSQL(false, false);
// 		exit;

// 		$this->derived_table = $derived->getSQL(false,false);
		$this->derived = $derived;
    }

    protected function dumpCSS()
    {
		parent::dumpCSS();

// 	echo "<link rel='stylesheet' href='".SITE_ROOT."css/demo.css' type='text/css'>";
// 	echo "<link rel='stylesheet' href='//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css'>";
// 	echo "\n";
    }
    
    protected function dumpJS()
    {
		parent::dumpJS();
	
// 	echo "<script src='//code.jquery.com/ui/1.11.4/jquery-ui.js'></script>";
// 	echo "<script src='".SITE_ROOT."lib/js/URI.js'></script>";
// 	echo "\n";
    }

    protected function dumpMetaTags()
	{
		parent::dumpMetaTags();
// 		echo "\n\n";
// 		echo "<meta name='viewport' content='width=960'>\n";
// 		echo "\n\n";
	}

    public function beginPage()
    {
		parent::beginPage();

		

    }


    public function finishPage()
    {


		parent::finishPage();
    }

}

?>