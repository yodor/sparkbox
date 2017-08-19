<?php
include_once ("lib/forms/InputForm.php");
include_once ("class/beans/BrandsBean.php");
include_once ("lib/input/validators/URLValidator.php");

class BrandInputForm extends InputForm
{


    public function __construct()
    {
	  $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "brand_name", "Brand Name", 1);
	  $this->addField($field);

  	  $field = InputFactory::CreateField(InputFactory::TEXTFIELD, "url", "Brand URL", 0);
  	  $field->setValidator(new URLValidator());
	  $this->addField($field);

  	  $field = InputFactory::CreateField(InputFactory::MCE_TEXTAREA, "summary", "Brand Summary", 0);
	  $this->addField($field);

	  
	  $field = InputFactory::CreateField(InputFactory::SESSION_IMAGE, "photo", "Photo", 0);
	  $this->addField($field);

    }

}
?>
