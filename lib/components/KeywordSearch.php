<?php
include_once("components/Component.php");
include_once("forms/KeywordSearchForm.php");
include_once("utils/IQueryFilter.php");
include_once("utils/IRequestProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class KeywordSearch extends FormRenderer implements IRequestProcessor
{

    const ACTION_SEARCH = "search";
    const ACTION_CLEAR = "clear";

    const SUBMIT_KEY = "filter";

    protected bool $have_filter = FALSE;


    public function __construct()
    {

        $this->form = new KeywordSearchForm();

        $input = $this->form->getInput("keyword");
        $input->getRenderer()->input()?->setAttribute("placeholder", $input->getLabel());

        parent::__construct($this->form);

        $this->setClassName("KeywordSearch");


        $this->setLayout(FormRenderer::FIELD_HBOX);

        //in admin pages is preferred POST as there are already some request conditions to be matched
        $this->setMethod(FormRenderer::METHOD_POST);

        $this->getButtons()->items()->clear();

        $submit_search = new ColorButton();
        $submit_search->setType(ColorButton::TYPE_SUBMIT);
        $submit_search->setContents("Search");
        $submit_search->setName(KeywordSearch::SUBMIT_KEY);
        $submit_search->setValue(KeywordSearch::ACTION_SEARCH);
        $submit_search->setAttribute("action", KeywordSearch::ACTION_SEARCH);
        $this->getButtons()->items()->append($submit_search);

        $submit_clear = new ColorButton();
        $submit_clear->setType(ColorButton::TYPE_SUBMIT);
        $submit_clear->setContents("Clear");
        $submit_clear->setName(KeywordSearch::SUBMIT_KEY);
        $submit_clear->setValue(KeywordSearch::ACTION_CLEAR);
        $submit_clear->setAttribute("action", KeywordSearch::ACTION_CLEAR);
        $this->getButtons()->items()->append($submit_clear);



    }

    public function processInput()
    {

        if (count($this->form->getFields()) < 1) return;

        $data = $_REQUEST;

        if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_CLEAR, $data)) {

            $url = URL::Current();
            $url->remove(KeywordSearch::SUBMIT_KEY);
            $url->remove("keyword");

            header("Location: " . $url->toString());
            exit;
        }
        else if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_SEARCH, $data)) {
            $this->form->loadPostData($data);
            $this->form->validate();
            $this->have_filter = TRUE;
        }

    }


    public function getButton(string $action): ?ColorButton
    {
        $result = $this->getButtons()->items()->getByAction($action);

        if ($result instanceof ColorButton) return $result;

        return null;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/KeywordSearch.css";
        return $arr;
    }

    public function isProcessed() : bool
    {
        return $this->have_filter;
    }

    public function getForm(): KeywordSearchForm
    {
        return $this->form;
    }

}
