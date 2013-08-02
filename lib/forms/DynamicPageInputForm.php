<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/selectors/ArraySelector.php");

class DynamicPageInputForm extends InputForm
{




    public function __construct()
    {


	$field = new InputField("item_title", "Title", 1);
	$field->setRenderer(new TextField());
	$this->addField($field);

	$field = new InputField("content", "Content", 1);
	$rend = new MCETextArea();
	$rend->setAttribute("rows", 20);
	$rend->setAttribute("cols", 80);
	$field->setRenderer($rend);
	$this->addField($field);

	$field = new InputField("item_date", "Date", 0);
	$field->setRenderer(new DateField());
	$field->setValidator(new DateValidator());
	$field->setProcessor(new DateInputProcessor());
	$this->addField($field);

	$field = new InputField("visible", "Visible", 0);
	$field->setRenderer(new CheckField());
	$this->addField($field);


	$field = new InputField("render_class", "CSS Class Name", 0);
	// 
	$sel = new ArraySelector(array("Notices"),"arr_id","arr_val");
	// 
	$rend = new SelectField();
	$rend->setSource($sel);
	// 
	$rend->na_str="Normal";
	$rend->na_val="";
	$rend->list_key="arr_id";
	$rend->list_label="arr_val";
	// 
	// 
	$field->setRenderer($rend);
	$this->addField($field);


	$field = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo", "Photo", 0);
	$field->transact_mode = InputField::TRANSACT_OBJECT;
	$field->getProcessor()->max_slots = 1;
	$this->addField($field);


	$this->getField("item_title")->enableTranslator(true);
	$this->getField("content")->enableTranslator(true);
    }

}
?>
