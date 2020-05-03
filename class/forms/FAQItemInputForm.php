<?php
include_once("lib/forms/InputForm.php");
include_once("lib/input/DataInputFactory.php");
include_once("lib/selectors/DBEnumSelector.php");

class FAQItemInputForm extends InputForm
{

    public function __construct()
    {

        $field = DataInputFactory::Create(DataInputFactory::SELECT, "section", "Section", 1);

        $enm = new DBEnumSelector("faq_items", "section");
        $rend = $field->getRenderer();
        $rend->setIterator($enm);
        $rend->list_key = "section";
        $rend->list_label = "section";

        $this->addField($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXT, "question", "Question", 1);
        $this->addField($field);

        $field = DataInputFactory::Create(DataInputFactory::TEXTAREA, "answer", "Answer", 1);
        $this->addField($field);

    }

}

?>