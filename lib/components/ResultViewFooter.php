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

        $link->add(new URLParameter(Paginator::KEY_PAGE));

        $a = $this->paginator->pageListStart();

        if ($this->paginator->currentPage() > 0) {

            $link->get(Paginator::KEY_PAGE)->setValue(0);
            echo "<a href='{$link->toString()}' title='".tr("First")."'> <<  </a>";

            $link->get(Paginator::KEY_PAGE)->setValue($this->paginator->currentPage() - 1);
            echo "<a rel='prev' href='{$link->toString()}' title='".tr("Prev")."'> <  </a>";

        }

        while ($a < $this->paginator->pageListEnd()) {

            $link->get(Paginator::KEY_PAGE)->setValue($a);

            $link_class = "";
            $rel = "";
            if ($this->paginator->currentPage() == $a) {
                $link_class = "class=selected";
            }
            if ($this->paginator->currentPage()+1 == $a) {
                $rel = "rel='next'";
            }
            if ($this->paginator->currentPage()-1 == $a) {
                $rel = "rel='prev'";
            }

            echo "<a $rel $link_class  href='{$link->toString()}'>" . ($a + 1) . "</a>";
            $a++;
        }

        if (($this->paginator->currentPage() + 1) < $this->paginator->totalPages()) {

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->currentPage() + 1));
            echo "<a rel='next' href='{$link->toString()}' title='".tr("Next")."'> > </a>";

            $link->get(Paginator::KEY_PAGE)->setValue(($this->paginator->totalPages() - 1));
            echo "<a  href='{$link->toString()}' title='".tr("Last")."'> >> </a>";
        }

    }
}

?>
