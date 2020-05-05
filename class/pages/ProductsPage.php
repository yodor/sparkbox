<?php
include_once("class/pages/DemoPage.php");
include_once("class/utils/ProductsQuery.php");
include_once("lib/components/renderers/ActionRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");

class ProductsPage extends DemoPage
{

    public $derived_table = NULL;
    public $derived = NULL;
    public $action_renderer = NULL;
    public $product_categories = NULL;

    public function __construct()
    {
        parent::__construct();

        $this->product_categories = new ProductCategoriesBean();

        $this->action_renderer = new ActionRenderer();

        $derived = new ProductsSQL();

        //  		echo $derived->getSQL(false, false);
        // 		exit;
        // 		$this->derived_table = $derived->getSQL(false,false);

        $this->derived = $derived;

        $this->addCSS(SITE_ROOT . "css/ProductsPage.css");
    }

    public function renderCategoryPath($nodeID)
    {


        $category_path = array();

        if ($nodeID > 0) {
            $category_path = $this->product_categories->parentCategories($nodeID);
        }

        $root_path = SITE_ROOT . "related_tree.php"; //$_SERVER["SCRIPT_NAME"]; //SITE_ROOT."products/list.php";

        $root_title = tr("Home");
        $root_action = new Action($root_title, $root_path, array());

        if (strcmp_isset("filter", "search")) {
            $root_title = tr("Search");
            $root_action = new Action($root_title, queryString(), array());
            Session::Set("search_home", "related_tree.php" . queryString());
        }
        else if (strcmp_isset("filter", "promo")) {
            $root_title = tr("Promo Products");
            $root_action = new Action($root_title, $root_path, array());
        }
        else {
            $search_home = (Session::Get("search_home", false));
            if ($search_home) {
                $root_action = new Action(tr("Search"), $search_home, array());
            }
        }
        echo "<div class='caption category_path'>";


        $actions[] = $root_action;


        foreach ($category_path as $idx => $category) {
            $qarr["catID"] = $category["catID"];

            $link = SITE_ROOT . "related_tree.php" . queryString($qarr);
            $actions[] = new Action($category["category_name"], $link, array());

        }
        $this->action_renderer->renderActions($actions);

        echo "</div>";


    }

}

?>
