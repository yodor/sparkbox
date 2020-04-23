<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");
include_once("class/beans/AttributesBean.php");
include_once("class/beans/ClassAttributesBean.php");
include_once("lib/input/ArrayDataInput.php");

class ProductClassInputForm extends InputForm
{

    public function __construct()
    {

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "class_name", "Class Name", 1);
        $this->addField($field);
        $field->enableTranslator(false);


        $field1 = new ArrayDataInput("attribute_name", "Attribute", 0);
        $field1->allow_dynamic_addition = true;
        $field1->setSource(new ClassAttributesBean());
        // 	  $field1->getValueTransactor()->process_datasource_foreign_keys = true;
        $field1->getValueTransactor()->bean_copy_fields = array("class_name");


        $attribs = new AttributesBean();

        $rend = new SelectField();
        $rend->setSource($attribs);
        $rend->list_key = "name";
        $rend->list_label = "name";


        $field1->setValidator(new EmptyValueValidator());

        $field1->setRenderer($rend);

        $arend = new ArrayField();
        $act_rend = new ActionRenderer(new Action("New attribute", "../attributes/add.php", array()));
        $act_rend->setName("New Attribute");
        $act_rend->setAttribute("action", "inline-new");
        $arend->addControl($act_rend);

        $field1->setArrayRenderer($arend);

        $this->addField($field1);

    }

}

?>
