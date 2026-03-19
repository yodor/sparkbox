<?php
class DatedArchiveInitScript extends InlinePageScript implements IPageComponent
{

    public function code(): string
    {
        $name = $this->getName();

        return <<<JS
let archive = new DatedArchive();
archive.setName("$name");
archive.initialize();
JS;

    }
}

class DatedArchive extends Container {

    protected ?DatedBean $bean = null;
    //parametrized
    protected ?URL $url = null;

    protected int $selectedYear = -1;
    protected int $selectedMonth = -1;

    public function requiredScript(): array
    {
        $arr = parent::requiredScript();
        $arr[] = Spark::Get(Config::SPARK_LOCAL)."/js/DatedArchive.js";
        return $arr;
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/animations.css";
        return $arr;
    }

    public function __construct(DatedBean $bean, URL $moduleURL)
    {
        parent::__construct(false);
        $this->setComponentClass("DatedArchive");
        $this->setName(get_class($bean));

        $this->bean = $bean;

        $this->url = new URL($moduleURL->toString());
        $this->url->add(new DataParameter("year"));
        $this->url->add(new DataParameter("month"));
        $this->url->remove($this->bean->key());

        $currentURL = URL::Current();
        if ($currentURL->contains("year")) {
            $this->selectedYear = (int)$currentURL->get("year")->value();
        }
        if ($currentURL->contains("month")) {
            $this->selectedMonth = (int)$currentURL->get("month")->value();
        }

        $yearList = $this->bean->years();

        foreach ($yearList as $idx=>$year) {

            $yearContainer = $this->createYearContainer($year);
            $this->items()->append($yearContainer);

        }

        $script = new DatedArchiveInitScript();
        $script->setName($this->getName());

    }

    protected function createYearContainer(int $year) : Container
    {
        $cntYear = new Container(false);
        $cntYear->setComponentClass("year");

        if ($this->selectedYear == $year) {
            $cntYear->addClassName("selected open");
        }

        $label = new Component(false);
        $label->setComponentClass("label");
        $label->setContents($year);

        $cntYear->items()->append($label);

        $cntMonths = $this->createMonthsContainer($year);

        $cntYear->items()->append($cntMonths);

        return $cntYear;
    }

    protected function createMonthsContainer(int $year) : Container
    {

        $months = $this->bean->months($year);

        $monthsList = new Container(false);
        $monthsList->setComponentClass("months");

        for ($month = 1; $month <= 12; $month++) {

            $monthName = DatedBean::MonthName($month);

            $have_data = in_array($month, $months);

            $cmp = new Component(false);
            $cmp->setComponentClass("item");
            $cmp->setTagName("span");

            if ($have_data > 0) {
                $this->url->setData(["year"=>$year,"month"=>$month]);

                $cmp->setTagName("a");
                $cmp->setAttribute("href", $this->url);

                if ($this->selectedYear == $year && $this->selectedMonth == $month) {
                    $cmp->addClassName("selected");
                }
            }

            $cmp->setContents($monthName);
            $monthsList->items()->append($cmp);
        }

        return $monthsList;
    }

}