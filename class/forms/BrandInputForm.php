<?php
include_once("lib/forms/InputForm.php");
include_once("class/beans/BrandsBean.php");
include_once("lib/input/validators/URLValidator.php");

class BrandInputForm extends InputForm
{


    public function __construct()
    {
        $field = DataInputFactory::Create(DataInputFactory::TEXTFIELD, "brand_name", "Brand Name", 1);
        $this->addField($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXTFIELD, "url", "Brand URL", 0);
        $field->setValidator(new URLValidator());
        $this->addField($field);

        $field = DataInputFactory::Create(DataInputFactory::MCE_TEXTAREA, "summary", "Brand Summary", 0);
        $this->addField($field);


        $field = DataInputFactory::Create(DataInputFactory::SESSION_IMAGE, "photo", "Photo", 0);
        $this->addField($field);

    }

}

?>
