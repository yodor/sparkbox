<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");
include_once ("class/beans/AttributesBean.php");
include_once ("class/beans/ClassAttributesBean.php");
include_once ("lib/input/ArrayInputField.php");

class ProductClassInputForm extends InputForm
{

    public function __construct()
    {

	  $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "class_name", "Class Name", 1);
	  $this->addField($field);
	  $field->enableTranslator(true);
	  
	  
	  $field1 = new ArrayInputField("attribute_name", "Attribute", 0);
	  $field1->allow_dynamic_addition=true;
	  $field1->setSource(new ClassAttributesBean());
// 	  $field1->getValueTransactor()->process_datasource_foreign_keys = true;
	  $field1->getValueTransactor()->bean_copy_fields = array("class_name");
	  
	  $attribs = new AttributesBean();

	  $rend = new SelectField();
	  $rend->setSource($attribs);
	  $rend->list_key="name";
	  $rend->list_label="name";

	  $field1->setValidator(new EmptyValueValidator());
	  
	  $field1->setRenderer($rend);
	  $this->addField($field1);

	}

}
?>
