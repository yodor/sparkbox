<?php
include_once("components/Component.php");
include_once("forms/KeywordSearchForm.php");
include_once("utils/IQueryFilter.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class KeywordSearchComponent extends Container implements IQueryFilter
{

    const ACTION_SEARCH = "search";
    const ACTION_CLEAR = "clear";

    const SUBMIT_KEY = "filter";

    protected $table_fields = array();

    protected $have_filter = FALSE;

    protected $form;
    protected $formRenderer;

    public function __construct(array $table_fields)
    {
        parent::__construct();

        $this->table_fields = $table_fields;

        $this->form = new KeywordSearchForm($this->table_fields);

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

        $this->formRenderer = new FormRenderer($this->form);
        //$this->formRenderer->setLayout(FormRenderer::FIELD_VBOX);

        $this->formRenderer->getButtons()->clear();

        $submit_search = StyledButton::DefaultButton();
        $submit_search->setType(StyledButton::TYPE_SUBMIT);
        $submit_search->setText(tr("Search"));
        $submit_search->setName(KeywordSearchComponent::SUBMIT_KEY);
        $submit_search->setValue(KeywordSearchComponent::ACTION_SEARCH);
        $submit_search->setAttribute("action", KeywordSearchComponent::ACTION_SEARCH);
        $this->formRenderer->getButtons()->append($submit_search);

        $submit_clear = StyledButton::DefaultButton();
        $submit_clear->setType(StyledButton::TYPE_SUBMIT);
        $submit_clear->setText(tr("Clear"));
        $submit_clear->setName(KeywordSearchComponent::SUBMIT_KEY);
        $submit_clear->setValue(KeywordSearchComponent::ACTION_CLEAR);
        $submit_clear->setAttribute("action", KeywordSearchComponent::ACTION_CLEAR);
        $this->formRenderer->getButtons()->append($submit_clear);

        $this->append($this->formRenderer);

    }

    //TODO: check usage
    public function getButton(string $action) : StyledButton
    {
        $comparator = function (Component $cmp) use ($action) {
            if (strcmp($cmp->getAttribute("action"), $action) == 0) {
                return TRUE;
            }
            return FALSE;
        };

        $result = $this->formRenderer->getButtons()->findBy($comparator);
        if ($result instanceof StyledButton) return $result;
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
