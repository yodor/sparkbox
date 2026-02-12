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
    protected Action $sortDir;

    public function __construct(Paginator $paginator)
    {
        parent::__construct(false);
        $this->setComponentClass("ResultViewHeader");
        $this->setTagName("header");

        $this->paginator = $paginator;

        $this->viewMode = new Container(false);
        $this->viewMode->setComponentClass("view_mode");

        $link = URL::Current();
        $link->remove(Paginator::KEY_PAGE);
        $link->add(new URLParameter(Paginator::KEY_VIEW, "list"));

        $button_list = Button::Action("", $link->toString());
        $button_list->setComponentClass("icon list");
        $this->viewMode->items()->append($button_list);

        $link->get(Paginator::KEY_VIEW)->setValue("grid");
        $button_grid = Button::Action("", $link->toString());
        $button_grid->setComponentClass("icon grid");
        $this->viewMode->items()->append($button_grid);

        $this->viewMode->setRenderEnabled(false);

        $this->items()->append($this->viewMode);

        $this->viewCaption = new TextComponent();
        $this->viewCaption->setComponentClass("caption");
        $this->viewCaption->setRenderEnabled(false);
        $this->items()->append($this->viewCaption);


        $dataInput = DataInputFactory::Create(InputType::SELECT, Paginator::KEY_ORDER_BY, tr("Sort By"), 0);
        $dataInput->getRenderer()->setIterator(new ArrayDataIterator());
        $dataInput->getRenderer()->input()->setAttribute("onChange", "changeSort(this);");
        $this->sort = new InputComponent($dataInput);
        $this->sort->setComponentClass("sort_fields");

        $this->sortDir = new Action();
        $this->sortDir->setComponentClass("direction");

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



        $link = SparkPage::Instance()->currentURL();
        $link->remove(Paginator::KEY_PAGE);
        $link->add(new URLParameter(Paginator::KEY_ORDER_BY, ""));
        $link->add(new URLParameter(Paginator::KEY_ORDER_DIR, ""));

        $selectedField = $this->paginator->getSelectedOrderColumn();

        $items = $this->paginator->getOrderColumns();

        //$iterator = $this->sort->getDataInput()->getRenderer()->getIterator();
        $iterator = new ArrayDataIterator();
        foreach ($items as $column => $sortField) {
            if (!($sortField instanceof OrderColumn)) continue;

            $link->get(Paginator::KEY_ORDER_BY)->setValue($sortField->getName());
            $link->get(Paginator::KEY_ORDER_DIR)->setValue($sortField->getDirection());

            if ($selectedField && strcmp($sortField->getName(), $selectedField->getName()) == 0) {
                $this->sort->getDataInput()->setValue($link->toString());
            }

            $iterator->appendValue($link->toString(), tr($sortField->getLabel()));
        }

        $renderer = $this->sort->getDataInput()->getRenderer();
        if ($renderer instanceof SelectField) {
            $renderer->setDefaultOption(null);
            $renderer->getItemRenderer()->setValueKey(ArrayDataIterator::KEY_ID);
            $renderer->getItemRenderer()->setLabelKey(ArrayDataIterator::KEY_VALUE);
            $renderer->setIterator($iterator);
        }
        $activeOrdering = $this->paginator->getActiveOrdering();

        //next click toggle direction
        $direction = OrderColumn::ASC;
        if (strcmp($activeOrdering->getDirection(), OrderColumn::ASC) == 0) {
            $direction = OrderColumn::DESC;
        }
        $link->get(Paginator::KEY_ORDER_DIR)->setValue($direction);

        $this->sortDir->setURL($link);
        $this->sortDir->setAttribute("direction", $direction);


        ?>
        <script type='text/javascript'>
            function decodeHtmlEntities(text) {
                const entities = {
                    '&amp;': '&',
                    '&lt;': '<',
                    '&gt;': '>',
                    '&quot;': '"',
                    '&#39;': "'",
                    // Add more entities as needed
                };

                // Replace named entities
                let decodedText = text.replace(/&amp;|&lt;|&gt;|&quot;|&#39;/g, (match) => entities[match]);

                // Replace numeric entities (e.g., &#123;)
                decodedText = decodedText.replace(/&#(\d+);/g, (match, dec) => String.fromCharCode(dec));

                return decodedText;
            }
            function changeSort(sel) {
                window.location.href = decodeHtmlEntities(sel.options[sel.options.selectedIndex].value);
            }
        </script>
        <?php
        parent::startRender();
    }

}