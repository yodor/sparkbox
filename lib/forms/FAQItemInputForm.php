<?php
include_once("forms/InputForm.php");
include_once("input/DataInputFactory.php");
include_once("beans/FAQSectionsBean.php");

class FAQItemInputForm extends InputForm
{

    public function __construct()
    {

        parent::__construct();

        $field = DataInputFactory::Create(InputType::SELECT, "fqsID", "Section", 1);

        $bean = new FAQSectionsBean();

        $rend = $field->getRenderer();

        $qry = $bean->query();
        $qry->select->fields()->set($bean->key(), "section_name");
        $rend->setIterator($qry);

        $rend->getItemRenderer()->setValueKey($bean->key());
        $rend->getItemRenderer()->setLabelKey("section_name");

        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXT, "question", "Question", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

        $field = DataInputFactory::Create(InputType::TEXTAREA, "answer", "Answer", 1);
        $field->enableTranslator(true);
        $this->addInput($field);

    }

}