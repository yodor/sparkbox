<?php
include_once("class/pages/DemoPage.php");
include_once("class/utils/ProductsQuery.php");

class ProductsPage extends DemoPage
{

    public $derived_table = NULL;
    public $derived = NULL;
	
    public function __construct()
    {
        parent::__construct();
        

        $derived = new ProductsQuery();
        
//  		echo $derived->getSQL(false, false);
// 		exit;
// 		$this->derived_table = $derived->getSQL(false,false);

        $this->derived = $derived;
    }

    protected function dumpCSS()
    {
        parent::dumpCSS();

    }
    
    protected function dumpJS()
    {
        parent::dumpJS();

    }

    protected function dumpMetaTags()
    {
        parent::dumpMetaTags();
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
