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

    protected $have_filter = FALSE;

    /**
     * @var KeywordSearchForm
     */
    protected $form;

    public function __construct()
    {

        $this->form = new KeywordSearchForm();

        $input = $this->form->getInput("keyword");
        $input->getRenderer()->setInputAttribute("placeholder", $input->getLabel());

        parent::__construct($this->form);

        $this->setLayout(FormRenderer::FIELD_HBOX);

        //in admin pages is preferred POST as there are already some request conditions to be matched
        $this->setMethod(FormRenderer::METHOD_POST);

        $this->getButtons()->clear();

        $submit_search = new ColorButton();
        $submit_search->setType(ColorButton::TYPE_SUBMIT);
        $submit_search->setContents("Search");
        $submit_search->setName(KeywordSearch::SUBMIT_KEY);
        $submit_search->setValue(KeywordSearch::ACTION_SEARCH);
        $submit_search->setAttribute("action", KeywordSearch::ACTION_SEARCH);
        $this->getButtons()->append($submit_search);

        $submit_clear = new ColorButton();
        $submit_clear->setType(ColorButton::TYPE_SUBMIT);
        $submit_clear->setContents("Clear");
        $submit_clear->setName(KeywordSearch::SUBMIT_KEY);
        $submit_clear->setValue(KeywordSearch::ACTION_CLEAR);
        $submit_clear->setAttribute("action", KeywordSearch::ACTION_CLEAR);
        $this->getButtons()->append($submit_clear);



    }

    public function processInput()
    {

        if (count($this->form->getFields()) < 1) return;

        $qry = $_REQUEST;

        if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_CLEAR, $qry)) {

            $url = new URLBuilder();
            $url->buildFrom(SparkPage::Instance()->getPageURL());
            //$url->removeParameter(KeywordSearch::SUBMIT_KEY);
            //$this->form->clearURLParameters($url);
            header("Location: " . $url->url());
            exit;
        }
        else if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_SEARCH, $qry)) {
            $this->form->loadPostData($qry);
            $this->form->validate();
            $this->have_filter = TRUE;
        }

    }

    //TODO: check usage
    public function getButton(string $action): ColorButton
    {
        $comparator = function (Component $cmp) use ($action) {
            if (strcmp($cmp->getAttribute("action"), $action) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $result = $this->getButtons()->findBy($comparator);
        if ($result instanceof ColorButton) return $result;
        return $result;
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