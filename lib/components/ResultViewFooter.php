<?php
include_once("components/Container.php");

class ResultViewFooter extends Container
{
    protected LabelSpan $pageInfo;
    protected LabelSpan $resultsInfo;
    protected Paginator $paginator;

    public function __construct(Paginator $paginator)
    {
        parent::__construct(false);
        $this->setComponentClass("ResultViewFooter");
        $this->setTagName("footer");

        $this->paginator = $paginator;

        $this->pageInfo = new LabelSpan(tr("Page"), "1 / 1");
        $this->pageInfo->setComponentClass("page_info");
        $this->items()->append($this->pageInfo);

        $pager = new ClosureComponent($this->renderPageSelector(...));
        $pager->setComponentClass("pager");
        $pager->setTagName("nav");
        $pager->setAttribute("aria-label", "Product listing pagination");
        $this->items()->append($pager);

        $this->resultsInfo = new LabelSpan(tr("Results"), "0");
        $this->resultsInfo->setComponentClass("results_info");
        $this->items()->append($this->resultsInfo);
    }

    protected function processAttributes(): void
    {
        parent::processAttributes();

        $text = "0";
        if ($this->paginator->totalPages() > 0) {
            $text = ($this->paginator->currentPage() + 1)." / ".$this->paginator->totalPages();
        }
        $this->pageInfo->span()->setContents($text);

        $this->resultsInfo->span()->setContents($this->paginator->resultsTotal());
    }

    protected function renderPageSelector() : void
    {

        //allow slugified urls
        $link = SparkPage::Instance()->currentURL();
        $supported_params = SparkPage::Instance()->getParameterNames();

        $paginator_params = $this->paginator->getParameterNames();
        foreach ($paginator_params as $idx=>$name) {
            if (str_contains($name, Paginator::KEY_PAGE))continue;
            $supported_params[] = $name;
        }

        URL::Clean($link, $supported_params);

        $action = new Action();
        $action->setComponentClass("");
        $action->setClassName("");
        $action->setURL($link);

        $a = $this->paginator->pageListStart();

        if ($this->paginator->currentPage() > 0) {

            $link->remove(Paginator::KEY_PAGE);
            $action->setTitle(tr("First"));
            $action->setContents(" <<  ");
            $action->render();

            if ($this->paginator->currentPage() - 1 > 0) {
                $link->add(new URLParameter(Paginator::KEY_PAGE, $this->paginator->currentPage() - 1));
            }
            $action->setTitle(tr("Prev"));
            $action->setContents(" <  ");
            $action->setAttribute("rel", "prev");
            $action->render();

        }

        while ($a < $this->paginator->pageListEnd()) {

            $link->add(new URLParameter(Paginator::KEY_PAGE, $a));
            if ($a<1) {
                $link->remove(Paginator::KEY_PAGE);
            }

            $action->setClassName("");
            $action->removeAttribute("rel");
            $action->removeAttribute("aria-current");

            if ($this->paginator->currentPage() == $a) {
                $action->setClassName("selected");
                $action->setAttribute("aria-current","page");
            }
            else if ($this->paginator->currentPage()+1 == $a) {
                $action->setAttribute("rel", "next");
            }
            else if ($this->paginator->currentPage()-1 == $a) {
                $action->setAttribute("rel", "prev");
            }

            $action->setTitle(($a+1));
            $action->setContents(($a+1));
            $action->render();

            $a++;
        }

        $action->setClassName("");
        $action->removeAttribute("rel");
        $action->removeAttribute("aria-current");

        if (($this->paginator->currentPage() + 1) < $this->paginator->totalPages()) {

            $link->add(new URLParameter(Paginator::KEY_PAGE, ($this->paginator->currentPage() + 1)));
            $action->setAttribute("rel","next");
            $action->setTitle(tr("Next"));
            $action->setContents(" > ");
            $action->render();

            $link->add(new URLParameter(Paginator::KEY_PAGE, ($this->paginator->totalPages() - 1)));
            $action->removeAttribute("rel");
            $action->setTitle(tr("Last"));
            $action->setContents(" >> ");
            $action->render();
        }

    }
}

?>
