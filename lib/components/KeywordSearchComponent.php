<?php
include_once("components/Component.php");
include_once("forms/KeywordSearchForm.php");
include_once("utils/IQueryFilter.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class KeywordSearchComponent extends FormRenderer implements IQueryFilter
{

    const ACTION_SEARCH = "search";
    const ACTION_CLEAR = "clear";

    const SUBMIT_KEY = "filter";

    protected $table_fields = array();

    protected $have_filter = FALSE;

    protected $form;

    public function __construct(array $table_fields)
    {

        $this->table_fields = $table_fields;

        $this->form = new KeywordSearchForm($this->table_fields);

        $input = $this->form->getInput("keyword");
        $input->getRenderer()->setInputAttribute("placeholder", $input->getLabel());

        parent::__construct($this->form);

        $qry = $_REQUEST;

        if (strcmp_isset(KeywordSearchComponent::SUBMIT_KEY, KeywordSearchComponent::ACTION_CLEAR, $qry) === TRUE) {

            $this->form->clearQuery($qry);
            unset($qry[KeywordSearchComponent::SUBMIT_KEY]);

            $qstr = queryString($qry);
            $loc = $_SERVER["PHP_SELF"] . "$qstr";

            header("Location: $loc");
            exit;
        }
        else if (strcmp_isset(KeywordSearchComponent::SUBMIT_KEY, KeywordSearchComponent::ACTION_SEARCH, $qry) === TRUE) {
            $this->form->loadPostData($qry);
            $this->form->validate();
            $this->have_filter = TRUE;
        }

        $this->setLayout(FormRenderer::FIELD_HBOX);

        $this->getButtons()->clear();

        $submit_search = new ColorButton();
        $submit_search->setType(ColorButton::TYPE_SUBMIT);
        $submit_search->setContents("Search");
        $submit_search->setName(KeywordSearchComponent::SUBMIT_KEY);
        $submit_search->setValue(KeywordSearchComponent::ACTION_SEARCH);
        $submit_search->setAttribute("action", KeywordSearchComponent::ACTION_SEARCH);
        $this->getButtons()->append($submit_search);

        $submit_clear = new ColorButton();
        $submit_clear->setType(ColorButton::TYPE_SUBMIT);
        $submit_clear->setContents("Clear");
        $submit_clear->setName(KeywordSearchComponent::SUBMIT_KEY);
        $submit_clear->setValue(KeywordSearchComponent::ACTION_CLEAR);
        $submit_clear->setAttribute("action", KeywordSearchComponent::ACTION_CLEAR);
        $this->getButtons()->append($submit_clear);

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
        $arr[] = SPARK_LOCAL . "/css/KeywordSearchComponent.css";
        return $arr;
    }

    public function haveFilter()
    {
        return $this->have_filter;
    }

    public function getForm(): InputForm
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
