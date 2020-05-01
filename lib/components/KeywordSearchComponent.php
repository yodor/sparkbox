<?php
include_once("lib/components/Component.php");
include_once("lib/forms/KeywordSearchForm.php");
include_once("lib/utils/IQueryFilter.php");
include_once("lib/forms/renderers/FormRenderer.php");

class KeywordSearchComponent extends Component implements IQueryFilter
{

    protected $sform = false;

    public $form_append = "";
    public $form_prepend = "";

    protected $buttons = array();

    const ACTION_SEARCH = "search";
    const ACTION_CLEAR = "clear";

    protected $table_name = "";
    protected $table_fields = "";

    protected $have_filter = false;

    public function getButton($action)
    {
        return $this->buttons[$action];

    }

    public function __construct($table_fields)
    {
        parent::__construct();

        $this->table_fields = $table_fields;

        $this->sform = new KeywordSearchForm($this->table_fields);

        $qry = $_REQUEST;

        if (strcmp_isset("clear", "search", $qry) === true) {

            $this->sform->clearQuery($qry);
            $qstr = queryString($qry);
            $loc = $_SERVER["PHP_SELF"] . "$qstr";

            header("Location: $loc");
            exit;
        }
        $this->sform->loadPostData($_REQUEST);
        $this->sform->validate();


        $submit_search = StyledButton::DefaultButton();
        $submit_search->setType(StyledButton::TYPE_SUBMIT);
        $submit_search->setText("Search");
        $submit_search->setName("filter");
        $submit_search->setValue("search");
        $submit_search->setAttribute("action", "search");
        $this->buttons[KeywordSearchComponent::ACTION_SEARCH] = $submit_search;

        $submit_clear = StyledButton::DefaultButton();
        $submit_clear->setType(StyledButton::TYPE_SUBMIT);
        $submit_clear->setText("Clear");
        $submit_clear->setName("clear");
        $submit_clear->setValue("search");
        $submit_clear->setAttribute("action", "clear");
        $this->buttons[KeywordSearchComponent::ACTION_CLEAR] = $submit_clear;

        $this->sform->setRenderer(new FormRenderer());

        if (isset($_GET["filter"])) {
            $this->sform->loadPostData($_GET);
            $this->sform->validate();
            $this->have_filter = true;
        }

    }

    public function haveFilter()
    {
        return $this->have_filter;
    }

    public function requiredStyle()
    {
        $arr = parent::requiredStyle();
        $arr[] = SITE_ROOT . "lib/css/KeywordSearchComponent.css";
        return $arr;
    }

    public function startRender()
    {
        parent::startRender();
        $this->sform->getRenderer()->startRender();

        echo $this->form_prepend;
    }

    public function finishRender()
    {
        echo $this->form_append;
        $this->sform->getRenderer()->finishRender();

        parent::finishRender();
    }

    public function getForm()
    {
        return $this->sform;
    }

    public function renderImpl()
    {
        echo "<div class='fields'>";

        $field = $this->sform->getField("keyword");
        $field->getLabelRenderer()->renderLabel($field);

        $field->getRenderer()->renderField($field);

        echo "</div>";

        echo "<div class='buttons'>";

        $submit_search = $this->buttons[KeywordSearchComponent::ACTION_SEARCH];
        $submit_search->render();

        $submit_clear = $this->buttons[KeywordSearchComponent::ACTION_CLEAR];
        $submit_clear->render();


        echo "</div>";


    }

    public function processSearch(SQLSelect &$select_query)
    {
        $search_query = $this->sform->searchFilterSelect();

        $select_query = $select_query->combineWith($search_query);

    }

    public function processSearchHaving(SQLSelect &$select_query)
    {
        $search_query = $this->sform->searchFilterSelect();


        $select_query->having = $search_query->where;

    }

    public function filterSelect($source = NULL, $value = NULL)
    {
        return $this->sform->searchFilterSelect();
    }

}
