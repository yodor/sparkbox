<?php
include_once("lib/forms/InputForm.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/AttributesBean.php");
include_once("class/beans/ClassAttributesBean.php");
include_once("lib/input/ArrayDataInput.php");

class ProductCategoryInputForm extends InputForm
{


    public function __construct()
    {
        $field = new DataInput("category_name", "Category Name", 1);
        $field->setRenderer(new TextField());
        $this->addField($field);

        $field = new DataInput("parentID", "Parent Category", 1);
        $pcats = new ProductCategoriesBean();

        $rend = new NestedSelectField();
        $rend->setIterator($pcats->query());
        $rend->list_key = "catID";
        $rend->list_label = "category_name";
        $rend->na_str = '--- TOP ---';
        $rend->na_val = "0";

        $field->setRenderer($rend);
        $this->addField($field);

        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 0);
        $this->addField($field);

        // 	  $field1 = new ArrayInputField("maID", "Attribute", 0);
        // 	  $field1->allow_dynamic_addition=true;
        // 	  $field1->setSource(new ClassAttributesBean());
        //
        // 	  $attribs = new AttributesBean();
        //
        // 	  $rend = new SelectField();
        // 	  $rend->setSource($attribs);
        // 	  $rend->list_key="maID";
        // 	  $rend->list_label="name";
        //
        // 	  $field1->setValidator(new EmptyValueValidator());
        //
        // 	  $field1->setRenderer($rend);
        // 	  $this->addField($field1);


        $this->getField("category_name")->enableTranslator(true);
    }

}

?>