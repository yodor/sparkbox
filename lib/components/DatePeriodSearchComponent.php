<?php
include_once("components/Component.php");
include_once("input/renderers/DateField.php");
include_once("components/InputComponent.php");
include_once("sql/SQLSelect.php");

class DatePeriodForm extends InputForm {

    public function __construct()
    {
        parent::__construct();
        $this->addInput(DataInputFactory::Create(DataInputFactory::DATE, "period_start", "Period Start", 0));
        $this->addInput(DataInputFactory::Create(DataInputFactory::DATE, "period_end", "Period End", 0));

    }
    public function prepareClauseCollection(string $oper = " AND ", string $field=""): ClauseCollection
    {
        $where = new ClauseCollection();
        $psd = $this->getInput("period_start")->getValue();
        $ped = $this->getInput("period_end")->getValue();

        if (strlen($psd) > 0) {

            $where->add($value,  "timestamp('$psd 00:00:00')", ">=");

        }
        if (strlen($ped) > 0) {

            $where->add($value, "timestamp('$ped 23:59:59')", "<=");

        }

    }

}
class DatePeriodSearchComponent extends FormRenderer implements IQueryFilter, IRequestProcessor
{
    protected $processed = false;


    //TODO: finish refactoring
    public function __construct()
    {

        $this->form = new InputForm();



        parent::__construct($this->form);


        $this->getSubmitLine()->clear();

        $filter_button = new ColorButton();
        $filter_button->setType(ColorButton::TYPE_SUBMIT);
        $filter_button->setContents("Filter Dates");
        $filter_button->setName("filter");
        $filter_button->setValue("dates");
        $this->getSubmitLine()->append($filter_button);

        $clear_button = new ColorButton();
        $clear_button->setType(ColorButton::TYPE_SUBMIT);
        $clear_button->setContents("Clear Filter");
        $clear_button->setName("filter");
        $clear_button->setValue("clear");
        $this->getSubmitLine()->append($clear_button);



    }

    public function processInput()
    {
        if (strcmp_isset("filter", "clear")) {

            $url = new URLBuilder();
            $url->buildFrom(SparkPage::Instance()->getPageURL());

            $this->form->clearURLParameters($url);

            header("Location: ".$url->url());

            exit;

        }
        else if (strcmp_isset("filter", "dates")) {
            $this->form->loadPostData($_GET);
            $this->form->validate();
            $this->processed = TRUE;
        }

    }


    public function getForm() : InputForm
    {
        return $this->form;
    }

    public function isProcessed(): bool
    {
        return $this->processed;

    }

    public function filterSelect($source = NULL, $value = NULL)
    {


    }
}