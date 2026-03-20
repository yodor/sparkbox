<?php
include_once("components/Container.php");
include_once("components/TextComponent.php");
include_once("components/LabelSpan.php");
include_once("components/InputComponent.php");
include_once("iterators/ArrayDataIterator.php");


class ResultViewHeader extends Container
{
    public bool $view_modes_enabled = FALSE;

    protected Paginator $paginator;

    protected Container $viewMode;

    protected TextComponent $viewCaption;

    protected Container $pageNav;
    protected LabelSpan $pageInfo;

    protected Action $pageNext;
    protected Action $pagePrev;

    protected InputComponent $sort;
    protected Component $sortDir;

    public function __construct(Paginator $paginator)
    {
        parent::__construct(false);
        $this->setComponentClass("ResultViewHeader");
        $this->setTagName("header");

        $this->paginator = $paginator;

        $this->viewMode = new Container(false);
        $this->viewMode->setComponentClass("view_mode");


        $buttonList = Button::TextButton("", "list");
        $buttonList->setComponentClass("icon list");
        $buttonList->setAttribute("key", Paginator::KEY_VIEW);
        $buttonList->setAttribute("onClick", "updateList(this)");
        $this->viewMode->items()->append($buttonList);

        $buttonGrid = Button::TextButton("", "grid");
        $buttonGrid->setComponentClass("icon grid");
        $buttonGrid->setAttribute("key", Paginator::KEY_VIEW);
        $buttonGrid->setAttribute("onClick", "updateList(this)");
        $this->viewMode->items()->append($buttonGrid);

        $this->viewMode->setRenderEnabled(false);

        $this->items()->append($this->viewMode);

        $this->viewCaption = new TextComponent();
        $this->viewCaption->setComponentClass("caption");
        $this->viewCaption->setRenderEnabled(false);
        $this->items()->append($this->viewCaption);


        $dataInput = DataInputFactory::Create(InputType::SELECT, Paginator::KEY_ORDER_BY, tr("Sort By"), 0);
        $dataInput->getRenderer()->setIterator(new ArrayDataIterator());
        $dataInput->getRenderer()->input()->setAttribute("onChange", "updateList(this)");
        $dataInput->getRenderer()->input()->setAttribute("key", Paginator::KEY_ORDER_BY);
        $this->sort = new InputComponent($dataInput);
        $this->sort->setComponentClass("sort_fields");

        //will use icon action holds the next direction and key the URL get key
        $this->sortDir = Button::TextButton("", OrderDirection::ASC->value);
        $this->sortDir->setComponentClass("direction");
        $this->sortDir->setAttribute("key", Paginator::KEY_ORDER_DIR);
        $this->sortDir->setAttribute("onClick", "updateList(this)");

        $this->sort->items()->append($this->sortDir);
        $this->items()->append($this->sort);


        $this->pageNav = new Container(false);
        $this->pageNav->setComponentClass("page_nav");

        $this->pageInfo = new LabelSpan(tr("Page"), "0");
        $this->pageNav->items()->append($this->pageInfo);

        $this->pagePrev = new Action();
        $this->pagePrev->setComponentClass("previous_page");
        $this->pagePrev->setContents(" < " . tr("Prev"));
        $this->pagePrev->setRenderEnabled(false);
        $this->pageNav->items()->append($this->pagePrev);

        $this->pageNext = new Action();
        $this->pageNext->setComponentClass("next_page");
        $this->pageNext->setContents(tr("Next") . " > ");
        $this->pageNext->setRenderEnabled(false);
        $this->pageNav->items()->append($this->pageNext);

        $this->pageNav->setRenderEnabled(false);

        $this->items()->append($this->pageNav);

        new UpdateListInlineScript();
    }

    protected function renderCaption(): void
    {

    }

    public function getViewMode() : Container
    {
        return $this->viewMode;
    }

    public function startRender(): void
    {
        if ($this->getCaption()) {
            $this->viewCaption->setContents($this->getCaption());
            $this->viewCaption->setRenderEnabled(true);
        }

        $items = $this->paginator->getOrderColumns();

        if (count($items) < 1) {
            $this->sort->setRenderEnabled(false);
        }

        $page = $this->paginator->currentPage() + 1;

        $this->pageInfo->span()->setContents("$page / " . $this->paginator->totalPages());

        //allow slugified urls
        $link = SparkPage::Instance()->currentURL();

        $link->add(new URLParameter(Paginator::KEY_PAGE));

        if ($this->paginator->currentPage() > 0) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() - 1));
            $this->pagePrev->setURL($link);
            $this->pagePrev->setRenderEnabled(true);
        }

        if (($this->paginator->currentPage() + 1) < $this->paginator->totalPages()) {
            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() + 1));
            $this->pageNext->setURL($link);
            $this->pageNext->setRenderEnabled(true);
        }

        $activeColumn = $this->paginator->getActiveOrder();

        $items = $this->paginator->getOrderColumns();

        //create select iterator values from paginator sorting columns
        $iterator = new ArrayDataIterator();
        foreach ($items as $columnName => $orderColumn) {
            if (!($orderColumn instanceof OrderColumn)) continue;

            if ($activeColumn && strcmp($orderColumn->getName(), $activeColumn->getName()) === 0) {
                $this->sort->getDataInput()->setValue($activeColumn->getName());
            }

            $label = $orderColumn->getLabel()??$orderColumn->getName();
            $iterator->appendValue($orderColumn->getName(), tr($label));
        }

        $renderer = $this->sort->getDataInput()->getRenderer();
        if ($renderer instanceof SelectField) {
            $renderer->setDefaultOption(null);
            $renderer->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
            $renderer->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
            $renderer->setIterator($iterator);
        }

        //prepare direction for next click toggle direction
        $direction = $activeColumn?->getDirection();
        $direction = $direction ?? OrderDirection::DESC;

        //icon shows the active listing direction
        $this->sortDir->setAttribute("direction", $direction->value);

        //toggle the direction - clicking the link will set the opposite direction
        $direction = $direction->opposite();
        $this->sortDir->setAction($direction->value);

        parent::startRender();
    }

}