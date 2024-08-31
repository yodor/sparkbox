<?php
include_once("beans/DatedBean.php");
include_once("components/Component.php");
include_once("utils/ValueInterleave.php");
include_once("components/ItemView.php");
include_once("components/renderers/items/DataIteratorItem.php");

class PublicationItem extends DataIteratorItem implements IPhotoRenderer
{

    protected URLBuilder $url;

    protected int $width = 64;
    protected int $height = 64;

    protected string $beanClass = "";

    protected $dateFormat = "j M Y";

    public function __construct(string $beanClass)
    {
        parent::__construct();
        $this->tagName = "A";

        $this->setClassName("item");
        $this->url = new URLBuilder();

        if (empty($beanClass)) throw new Exception("Empty bean class");
        $this->beanClass = $beanClass;
    }

    public function setURL(URLBuilder $url)
    {
        $this->url = $url;
    }

    public function getURL(): URLBuilder
    {
        return $this->url;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->url->setData($data);
        $this->setAttribute("href", $this->url->url());
        $this->setAttribute("itemID", $this->id);

    }

    protected function renderImpl()
    {
        echo "<div class='cell image'>";
        $img_href = StorageItem::Image($this->id, $this->beanClass, $this->width, $this->height);
        echo "<img src='$img_href'>";
        echo "</div>";

        echo "<div class='cell details'>";
        echo "<span class='title'>" . $this->data["item_title"] . "</span>";
        echo "<span class='date'>" . date($this->dateFormat, strtotime($this->data["item_date"])) . "</span>";
        echo "</div>";

        echo "</a>";

    }

    public function setDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setPhotoSize(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getPhotoWidth(): int
    {
        return $this->width;
    }

    public function getPhotoHeight(): int
    {
        return $this->height;
    }

}

class PublicationsComponent extends Container implements IRequestProcessor
{

    protected $bean;

    protected $selected_year = -1;
    protected $selected_month = -1;

    protected $columns;

    protected $url;

    protected $have_selection = FALSE;

    protected $selected_ID = array();

    protected $itemView;
    protected $itemRenderer;

    public function getSelectedYear(): int
    {
        return $this->selected_year;
    }

    public function getSelectedMonth(): int
    {
        return $this->selected_month;
    }

    public function isProcessed(): bool
    {
        return $this->have_selection;
    }

    public function getSelectionColumns(): array
    {
        return $this->columns;
    }

    public function __construct(DatedBean $bean, string $link_page = "", array $columns = array("item_title",
                                                                                                "item_date", "content"))
    {
        parent::__construct();

        $this->columns = $columns;
        $this->columns[] = $bean->key();

        $this->bean = $bean;

        $this->url = new URLBuilder();

        if ($link_page) {
            $this->url->buildFrom($link_page);
        }
        else {
            $this->url->buildFrom(currentURL());
        }

        $this->selected_ID = array();

        $qry = $this->bean->query(...$this->columns);
        $qry->select->order_by = $this->bean->getDateColumn() . " DESC";
        $qry->select->limit = 3;

        $this->itemView = new ItemView($qry);

        $this->itemView->enablePaginators(0);
        $this->itemView->setItemsPerPage(0);

        $this->itemRenderer = new PublicationItem(get_class($this->bean));
        $this->itemRenderer->getURL()->add(new DataParameter($bean->key()));

        $this->itemView->setItemRenderer($this->itemRenderer);



        $this->append($this->itemView);

    }

    public function getItemView(): ItemView
    {
        return $this->itemView;
    }

    public function getItemRenderer()
    {
        return $this->itemRenderer;
    }

    public function getSelection(): array
    {
        return $this->selected_ID;
    }

    public function requiredStyle(): array
    {
        $arr = parent::requiredStyle();
        $arr[] = SPARK_LOCAL . "/css/Publications.css";
        return $arr;
    }

    public function processInput()
    {

        $qry = $this->bean->query($this->bean->key(), $this->bean->getDateColumn());


        $num = -1;

        if (isset($_GET[$this->bean->key()])) {
            $itemID = (int)$_GET[$this->bean->key()];
            $qry->select->where()->add($this->bean->key(), $itemID);
            $num = $qry->exec();
        }
        else if (isset($_GET["year"]) && isset($_GET["month"])) {
            $qry->select->where()->add("MONTH({$this->bean->getDateColumn()})", (int)$_GET["month"]);
            $qry->select->where()->add("YEAR({$this->bean->getDateColumn()})", (int)$_GET["year"]);
            $num = $qry->exec();
        }

        if ($num < 1) {
            $qry->select->where()->clear();
            $qry->select->order_by = $this->bean->getDateColumn() . " DESC ";
            $qry->select->limit = 1;
            $qry->exec();
        }

        while ($item = $qry->next()) {
            $this->selected_ID[] = $item[$this->bean->key()];
            if (!$this->selected_month || $this->selected_year) {
                $this->selected_month = date("n", strtotime($item[$this->bean->getDateColumn()]));
                $this->selected_year = date("Y", strtotime($item[$this->bean->getDateColumn()]));
            }

        }

    }

    public function getBean(): DatedBean
    {
        return $this->bean;
    }

    public function renderArchive()
    {

        echo "<div class='archive'>";

        $month_list = array();

        for ($a=1;$a<=12;$a++) {
            $month_list[] = date("F", mktime(0, 0, 0, $a));
        }
        //$month_list = array("January", "February", "March", "April", "May", "June", "July", "August", "September",
         //                   "October", "November", "December");

        $v = new ValueInterleave();

        $cls = $v->value();

        $year_list = $this->bean->getYears();

        for ($a = 0; $a < count($year_list); $a++) {

            $year = $year_list[$a];
            echo "<div class='archive_year $cls' >";
            echo "<a onClick='toggleArchiveYear(" . $year . ")'>$year</a>";
            echo "</div>";

            echo "<div class='months' year='$year'>";

            echo "<div class='list'>";
            $c = 0;
            for ($b = 0; $b < count($month_list); $b++) {

                $month = $month_list[$b];

                if ($c == 0) echo "<div class='row'>";

                $have_data = $this->bean->publicationsCount($year, $b+1);

                if ($have_data > 0) {
                    $url = new URLBuilder();
                    $url->buildFrom($this->url->getBuildFrom());
                    $url->add(new URLParameter("year", $year));
                    $url->add(new URLParameter("month", $b+1));
                    $active = "";
                    if ( $b+1 == $this->selected_month) {
                        $active = "selected";
                    }
                    echo "<a href='{$url->url()}' $active class='item'>";
                    echo tr($month);
                    echo "</a>";
                }
                else {
                    echo "<span class='item'>";
                    echo tr($month);
                    echo "</span>";
                }

                $c++;
                if ($c == 3) {
                    echo "</div>";
                    $c = 0;
                }

            }

            $v->advance();
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";

        ?>
        <script type='text/javascript'>
            function toggleArchiveYear(year) {
                $(".months").css("display", "none");

                $(".months[year='" + year + "']").css("display", "block");

            }

            onPageLoad(function () {
                toggleArchiveYear(<?php echo $this->selected_year;?>);
            });
        </script>

        <?php

    }

    protected function renderImpl()
    {
        echo "<div class='Caption'>".tr("Latest News")."</div>";
        parent::renderImpl();

        echo "<div class='Caption'>".tr("News Archive")."</div>";
        $this->renderArchive();
    }

}
