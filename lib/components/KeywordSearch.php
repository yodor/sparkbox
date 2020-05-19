<?php
include_once("components/Component.php");
include_once("forms/KeywordSearchForm.php");
include_once("utils/IQueryFilter.php");
include_once("utils/IRequestProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class KeywordSearch extends FormRenderer implements IQueryFilter, IRequestProcessor
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

        if (count($this->form->getFields())<1)return;

        $qry = $_REQUEST;

        if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_CLEAR, $qry) === TRUE) {

            $this->form->clearQuery($qry);
            unset($qry[KeywordSearch::SUBMIT_KEY]);

            $qstr = queryString($qry);
            $loc = $_SERVER["PHP_SELF"] . "$qstr";

            header("Location: $loc");
            exit;
        }
        else if (strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_SEARCH, $qry) === TRUE) {
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

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/KeywordSearch.css";
        return $arr;
    }

    public function haveFilter()
    {
        return $this->have_filter;
    }

    public function getForm(): KeywordSearchForm
    {
        return $this->form;
    }

    public function processSearch(SQLSelect &$select_query)
    {
        $search_query = $this->form->searchFilterSelect();

        $select_query = $select_query->combineWith($search_query);

    }

    public function processSearchHaving(SQLSelect &$select_query)
    {
        $search_query = $this->form->searchFilterSelect();

        $select_query->having = $search_query->where;

    }

    public function filterSelect($source = NULL, $value = NULL)
    {
        return $this->form->searchFilterSelect();
    }

}
