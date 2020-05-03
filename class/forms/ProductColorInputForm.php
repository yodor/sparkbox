<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");
include_once("class/beans/ProductColorPhotosBean.php");
include_once("class/beans/StoreColorsBean.php");
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

    public function __construct($prodID)
    {


        $field = DataInputFactory::Create(DataInputFactory::SELECT, "color", "Color Code", 1);

        $rend = $field->getRenderer();
        $rend->setIterator(new StoreColorsBean());
        $rend->list_label = "color";
        $rend->list_key = "color";
        $rend->addon_content = "<a class='ActionRenderer' action='new' href='../../colors/add.php'>" . tr("New Color Code") . "</a>";

        $opt = $rend->getItemRenderer();
        $opt->addDataRowAttribute("color_code");

        $this->addField($field);
        // 	$field->enableTranslator(true);


        $input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "color_photo", "Color Chip", 0);
        // 	$input->setSource(new ProductPhotosBean());
        // 	$input->transact_mode = InputField::TRANSACT_OBJECT;
        // 	$input->getValueTransactor()->max_slots = 10;

        $input->transact_mode = DataInput::TRANSACT_OBJECT;
        $input->getValueTransactor()->max_slots = 1;
        $this->addField($input);


        $input = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photos", 0);
        $bean = new ProductColorPhotosBean();

        $input->setIterator($bean);

        $input->transact_mode = DataInput::TRANSACT_OBJECT;
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

        $item_row = parent::loadBeanData($editID, $bean);
        //       $pclrID = (int)$item_row["pclrID"];
        //       $this->getField("photo")->getSource()->setFilter(" pclrID ='$pclrID' ");

    }

    public function loadPostData(array $arr)
    {
        parent::loadPostData($arr);
        //       $pclrID = -1;
        //       $this->getField("photo")->getSource()->setFilter(" pclrID ='$pclrID' ");

    }
}

?>
