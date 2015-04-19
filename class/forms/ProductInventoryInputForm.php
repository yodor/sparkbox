<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");
include_once ("class/beans/ProductColorsBean.php");
include_once ("class/beans/ProductSizesBean.php");

class ProductInventoryInputForm extends InputForm
{

    public function __construct()
    {

		$field = InputFactory::CreateField(InputFactory::SELECT, "pclrID", "Color", 0);
		$field->getRenderer()->setSource(new ProductColorsBean());
		$field->getRenderer()->list_key = "pclrID";
		$field->getRenderer()->list_label = "color";		
		$this->addField($field);
	

		$field = InputFactory::CreateField(InputFactory::SELECT, "pszID", "Size", 0);
		$field->getRenderer()->setSource(new ProductSizesBean());
		$field->getRenderer()->list_key = "pszID";
		$field->getRenderer()->list_label = "size_value";
		$this->addField($field);

		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "stock_amount", "Stock Amount", 1);
		$this->addField($field);
		
		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "price", "Price", 0);
		$this->addField($field);
		
		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "buy_price", "Buy Price", 0);
		$this->addField($field);

		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "old_price", "Old Price", 0);
		$this->addField($field);
		
		$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "weight", "Weight", 0);
		$this->addField($field);
	
  }

}
?>
