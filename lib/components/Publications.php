<?php
include_once("components/ItemView.php");
include_once("beans/DatedBean.php");
include_once("components/renderers/items/DatedItem.php");
include_once("components/renderers/items/DatedHeadingItem.php");
include_once("components/DatedArchive.php");

class Publications
{
    protected ?ItemView $view = null;

    protected ?ItemView $latest = null;

    protected ?DatedArchive $archive = null;

    //module url
    protected ?URL $url = null;

    protected int $latestLimit = 3;

    protected ?DatedBean $bean = null;

    protected array $columns = array();

    public function __construct(DatedBean $bean, URL $moduleURL)
    {
        HTMLHead::Instance()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/Publications.css");

        $this->bean = $bean;

        $this->url = $moduleURL;

        $this->view = new ItemView();
        $this->view->addClassName("Publications");
        $this->view->addClassName(get_class($bean));
        $this->view->getHeader()->setRenderEnabled(false);
//        $this->view->getFooter()->setRenderEnabled(false);
        $this->view->setItemsPerPage(5);

        $rendererFull = new DatedItem($this->bean);
        $this->view->setItemRenderer($rendererFull);

        $this->columns = $rendererFull->collectDataKeys();

        //limit latest 3
        $this->latest = new ItemView();
        $this->latest->addClassName("Publications");
        $this->latest->addClassName(get_class($bean));
        $this->latest->addClassName("latest");
        $this->latest->getHeader()->setRenderEnabled(false);
        $this->latest->getFooter()->setRenderEnabled(false);
        //disable pagination and set limit to the iterator
        $this->latest->setItemsPerPage(0);

        $rendererShort = new DatedHeadingItem($this->bean, $this->url);
        $rendererShort->getThumbnail()->image()->setPhotoSize(64, 64);
        $this->latest->setItemRenderer($rendererShort);

        //all news items?
        $queryLatest = $this->bean->queryDefault(...$rendererShort->collectDataKeys());
        //do not paginate
        $queryLatest->stmt->limit($this->latestLimit);
        $this->latest->setIterator($queryLatest);

        $this->archive = new DatedArchive($this->bean, $this->url);
        $this->archive->addClassName(get_class($bean));
    }

    public function processInput() : void
    {
        if (isset($_GET[$this->bean->key()])) {
            $itemID = (int)$_GET[$this->bean->key()];
            $query = $this->bean->queryID($itemID, ...$this->columns);
            $this->view->getFooter()->setRenderEnabled(false);
        }
        else if (isset($_GET["year"]) && isset($_GET["month"])) {
            $year = (int)$_GET["year"];
            $month = (int)$_GET["month"];
            $query = $this->bean->queryMonthYear($year, $month, ...$this->columns);
            $this->view->getFooter()->setRenderEnabled(true);
        }
        else {
            $query = $this->bean->queryDefault(...$this->columns);
            $this->view->getFooter()->setRenderEnabled(true);
        }

        $this->view->setIterator($query);

    }

    public function getLatest() : ItemView
    {
        return $this->latest;
    }

    public function getArchive() : DatedArchive
    {
        return $this->archive;
    }

    public function getMain() : ItemView
    {
        return $this->view;
    }
}