<?php
include_once ("lib/forms/InputForm.php");
include_once ("lib/input/InputFactory.php");

class NewsItemInputForm extends InputForm
{




      public function __construct()
      {


	    $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "item_title", "Title", 1);
	    $this->addField($field);

	    $field = InputFactory::CreateField(InputFactory::DATE,"item_date", "Date", 1);
	    $this->addField($field);

	    $field = InputFactory::CreateField(InputFactory::MCE_TEXTAREA, "content", "Content", 1);
	    $this->addField($field);

	    $field = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo", "Photo", 1);
	    $field->transact_mode = InputField::TRANSACT_OBJECT;
	    $field->getProcessor()->max_slots = 1;
	    $this->addField($field);

      }

}
?>
