<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");
include_once("beans/FAQSectionsBean.php");

class FAQItemInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = DataInputFactory::Create(DataInputFactory::SELECT, "fqsID", "Section", 0);

        $source = new FAQSectionsBean();

        $rend = $field->getRenderer();

        $rend->setIterator($source->query());

        $rend->getItemRenderer()->setValueKey($source->key());
        $rend->getItemRenderer()->setLabelKey("section_name");

        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "question", "Question", 1);
        $this->addInput($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "answer", "Answer", 1);
        $this->addInput($field);

    }

}

?>