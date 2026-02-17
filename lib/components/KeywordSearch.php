<?php
include_once("components/Component.php");
include_once("forms/KeywordSearchForm.php");
include_once("utils/IQueryFilter.php");
include_once("utils/IRequestProcessor.php");
include_once("forms/renderers/FormRenderer.php");
include_once("components/TextComponent.php");

class SubmitSearchScript extends PageScript
{

    function code(): string
    {
        return <<<JS
        function submitSearch(event) {
    
            const submitter = event.submitter;
        
            if (!submitter) {
                console.warn('No submitter detected');
                return;
            }
        
            const buttonName = submitter.name;
            const buttonValue = submitter.value;
            
            const form = document.forms.KeywordSearchForm;
            const query = form.keyword.value;
            if (submitter.value === "search") {
                if (query.length < 3) {
                    showAlert("Input search term");
                    return false;
                }
                return true;
            }
            else if (submitter.value === "clear") {
                if (query.length > 0) {
                    return true;
                }
                else {
                    return false;
                }
            }
            
            return true;
        }
JS;
    }
}
class KeywordSearch extends FormRenderer implements IRequestProcessor
{

    const string ACTION_SEARCH = "search";
    const string ACTION_CLEAR = "clear";

    const string SUBMIT_KEY = "filter";

    protected bool $have_filter = FALSE;


    public function __construct()
    {

        new SubmitSearchScript();

        $this->form = new KeywordSearchForm();

        $input = $this->form->getInput("keyword");
        $input->getRenderer()->input()?->setAttribute("placeholder", $input->getLabel());

        parent::__construct($this->form);

        $this->setClassName("KeywordSearch");
        $this->setAttribute("role", "search");

        $this->setLayout(self::LAYOUT_HBOX);

        $this->setMethod(self::METHOD_GET);

        $this->getButtons()->items()->clear();

        $submit_search = new Button();
        $submit_search->setType(Button::TYPE_SUBMIT);
        $submit_search->setContents("Search");
        $submit_search->setName(KeywordSearch::SUBMIT_KEY);
        $submit_search->setValue(KeywordSearch::ACTION_SEARCH);
        $submit_search->setAttribute("action", KeywordSearch::ACTION_SEARCH);
        $this->getButtons()->items()->append($submit_search);

        $submit_clear = new Button();
        $submit_clear->setType(Button::TYPE_SUBMIT);
        $submit_clear->setContents("Clear");
        $submit_clear->setName(KeywordSearch::SUBMIT_KEY);
        $submit_clear->setValue(KeywordSearch::ACTION_CLEAR);
        $submit_clear->setAttribute("action", KeywordSearch::ACTION_CLEAR);
        $this->getButtons()->items()->append($submit_clear);

        $this->form->getRenderer()->setAttribute("onSubmit", "return submitSearch(event)");

    }

    public function processInput(): void
    {

        if (count($this->form->getColumns()) < 1) return;

        $data = $_REQUEST;

        if (Spark::strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_CLEAR, $data)) {

            $url = URL::Current();
            $url->remove(KeywordSearch::SUBMIT_KEY);
            foreach ($this->form->inputNames() as $idx=>$name) {
                $url->remove($name);
            }

            header("Location: " . $url->toString());
            exit;
        }
//        else if (isset($data["keyword"])) {
//            $this->form->loadPostData($data);
//            $this->form->validate();
//            $this->have_filter = TRUE;
//        }
        else if (Spark::strcmp_isset(KeywordSearch::SUBMIT_KEY, KeywordSearch::ACTION_SEARCH, $data)) {
            $this->form->loadPostData($data);
            $this->form->validate();
            if (!$this->form->haveErrors()) {
                foreach ($this->form->inputNames() as $idx=>$name) {
                    $input = $this->form->getInput($name);

                    if ($input->getValue()) {
                        $this->have_filter = TRUE;
                        break;
                    }

                }
            }
        }

    }


    public function getButton(string $action): ?Button
    {
        $result = $this->getButtons()->items()->getByAction($action);

        if ($result instanceof Button) return $result;

        return null;
    }

    public function requiredStyle() : array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/KeywordSearch.css";
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