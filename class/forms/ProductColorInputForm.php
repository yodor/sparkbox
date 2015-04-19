<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");
include_once ("class/beans/ProductColorPhotosBean.php");

// include_once ("class/beans/BrandsBean.php");
// include_once ("class/beans/GendersBean.php");
// include_once ("class/beans/ProductCategoriesBean.php");
// include_once ("class/beans/ProductFeaturesBean.php");
// include_once ("class/beans/ProductPhotosBean.php");
// include_once ("class/beans/ClassAttributeValuesBean.php");
// 
// include_once ("class/input/renderers/ClassAttributeField.php");
// include_once ("lib/input/transactors/CustomFieldTransactor.php");

class ProductColorInputForm extends InputForm
{

    public function __construct()
    {


   

	$field = InputFactory::CreateField(InputFactory::TEXTFIELD, "color", "Color Name", 1);
	$this->addField($field);
	$field->enableTranslator(true);


	$input = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "color_photo","Color Photo", 0);
// 	$input->setSource(new ProductPhotosBean());
// 	$input->transact_mode = InputField::TRANSACT_OBJECT;
// 	$input->getValueTransactor()->max_slots = 10;

	$input->transact_mode = InputField::TRANSACT_OBJECT;
	$input->getValueTransactor()->max_slots = 1;
	$this->addField($input);


	$input = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo","Photos", 0);
	$input->setSource(new ProductColorPhotosBean());
	$input->transact_mode = InputField::TRANSACT_OBJECT;
	$input->getValueTransactor()->max_slots = 10;

	$this->addField($input);

// 	$field = new ArrayInputField("value", "Optional Attributes", 0);
// 	$field->allow_dynamic_addition = false;
// 	$field->source_label_visible = true;
// 	$field->getValueTransactor()->process_datasource_foreign_keys = true;
// 
// 	$bean1 = new ClassAttributeValuesBean();
// 	$field->setSource($bean1);
// 
// 	$rend = new ClassAttributeField();
// 	$field->setRenderer($rend);
// 
// 	$this->addField($field);


  }
  public function loadBeanData($editID, DBTableBean $bean)
  {

      parent::loadBeanData($editID,  $bean);


  }
  public function loadPostData(array $arr)
  {
      parent::loadPostData($arr);
      

  }
}
?>
