<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ClassAttributesBean.php");
include_once("class/beans/ProductColorsBean.php");
include_once("class/beans/StoreSizesBean.php");
include_once("class/beans/InventoryAttributeValuesBean.php");

include_once("class/input/renderers/ClassAttributeField.php");
include_once("class/input/renderers/SourceRelatedField.php");


class ProductInventoryInputForm extends InputForm
{

    protected $prodID = -1;
    protected $product = array();

    public function __construct()
    {

        $field = DataInputFactory::Create(DataInputFactory::SELECT, "pclrID", "Color Scheme", 0);
        $pcb = new ProductColorsBean();
        $field->getRenderer()->setIterator($pcb->query());


        $field->getRenderer()->list_key = "pclrID";
        $field->getRenderer()->list_label = "color";
        $field->getValueTransactor()->renderer_source_copy_fields = array("color");
        $this->addInput($field);


        $field = DataInputFactory::Create(DataInputFactory::SELECT, "size_value", "Sizing", 0);
        $ssb = new StoreSizesBean();
        $field->getRenderer()->setIterator($ssb->query());
        $field->getRenderer()->list_key = "size_value";
        $field->getRenderer()->list_label = "size_value";

        $field->getRenderer()->addon_content = "<a class='ActionRenderer' action='inline-new' href='../../sizes/add.php'>" . tr("New Sizing Code") . "</a>";

        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "stock_amount", "Stock Amount", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "price", "Price", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "buy_price", "Buy Price", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "old_price", "Old Price", 0);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "weight", "Weight", 0);
        $this->addInput($field);

        $field = new ArrayDataInput("value", "Class Attributes", 0);
        $field->allow_dynamic_addition = false;
        $field->source_label_visible = true;


        $field->getValueTransactor()->process_datasource_foreign_keys = true;

        $bean1 = new InventoryAttributeValuesBean();
        $field->setSource($bean1);


        $rend = new SourceRelatedField();

        $cab = new ClassAttributesBean();
        $rend->setIterator($cab->query());

        $rend->list_key = "caID";
        $rend->list_label = "attribute_name";

        $field->setRenderer($rend);

        $this->addInput($field);
    }

    public function setProductID($prodID)
    {
        $this->prodID = (int)$prodID;

        $this->getInput("pclrID")->getRenderer()->getIterator()->select->where = " prodID='{$this->prodID}' ";

        $this->getInput("pclrID")->getRenderer()->addon_content = "<a class='ActionRenderer' action='inline-new' href='../color_gallery/add.php?prodID={$this->prodID}'>" . tr("New Color Scheme") . "</a>";

        // 	  $this->getField("size_value")->getRenderer()->setFilter(" WHERE prodID='{$this->prodID}' ");

        $prods = new ProductsBean();
        $this->product = $prods->getByID($this->prodID);


        $rend = $this->getInput("value")->getRenderer();

        $rend->setCaption(tr("Product Class") . ": " . $this->product["class_name"]);

        $rend->getIterator()->select->from.=" ca LEFT JOIN attributes attr ON attr.name = ca.attribute_name ";
        $rend->getIterator()->select->where = " ca.class_name='". $this->product["class_name"]."' ";
        $rend->getIterator()->select->fields = " ca.*, attr.unit as attribute_unit, attr.type attribute_type ";

        $this->getInput("price")->setValue($this->product["price"]);
        $this->getInput("buy_price")->setValue($this->product["buy_price"]);
        $this->getInput("old_price")->setValue($this->product["old_price"]);
        $this->getInput("weight")->setValue($this->product["weight"]);

    }

    public function loadBeanData($editID, DBTableBean $bean)
    {

        $item_row = parent::loadBeanData($editID, $bean);

        $rend = $this->getInput("value")->getRenderer();

        $rend->getIterator()->select->from.=" ca LEFT JOIN inventory_attribute_values iav ON iav.caID = ca.caID AND iav.piID=$editID LEFT JOIN attributes attr ON attr.name = ca.attribute_name ";
        $rend->getIterator()->select->where = " ca.class_name='". $this->product["class_name"]."' ";
        $rend->getIterator()->select->fields = " ca.*, iav.value, attr.unit as attribute_unit, attr.type attribute_type ";
    }

    public function loadPostData(array $arr) : void
    {
        parent::loadPostData($arr);

    }
}

?>
