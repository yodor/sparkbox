<?php
include_once("beans/DatedBean.php");
include_once("components/Component.php");
include_once("utils/ValueInterleave.php");
include_once("components/ItemView.php");
include_once("components/renderers/items/DataIteratorItem.php");

class PublicationItem extends DataIteratorItem implements IPhotoRenderer
{

    protected URL $url;

    protected int $width = 64;
    protected int $height = 64;

    protected string $beanClass = "";

    protected string $dateFormat = "d MMMM y";

    protected IntlDateFormatter $formatter;

    public function __construct(string $beanClass)
    {
        parent::__construct();
        $this->tagName = "a";

        $this->setClassName("item");
        $this->url = new URL();

        if (empty($beanClass)) throw new Exception("Empty bean class");
        $this->beanClass = $beanClass;

        $this->formatter = new IntlDateFormatter(
            locale:          Spark::Get(Config::DEFAULT_LOCALE),
            dateType:        IntlDateFormatter::NONE,   // no date part
            timeType:        IntlDateFormatter::NONE,   // no time part
            timezone:        'UTC',                     // timezone usually irrelevant here
            calendar:        IntlDateFormatter::GREGORIAN,
            pattern:         $this->dateFormat
        );
    }

    public function setURL(URL $url) : void
    {
        $this->url = $url;
    }

    public function getURL(): URL
    {
        return $this->url;
    }

    public function setData(array $data) : void
    {
        parent::setData($data);
        $this->url->setData($data);
        $this->setAttribute("href", $this->url->toString());
        $this->setAttribute("itemID", $this->id);

    }

    protected function renderImpl(): void
    {
        echo "<div class='cell image'>";
        $img_href = StorageItem::Image($this->id, $this->beanClass, $this->width, $this->height);
        echo "<img src='$img_href'>";
        echo "</div>";

        echo "<div class='cell details'>";
        echo "<span class='title'>" . $this->data["item_title"] . "</span>";
        echo "<span class='date'>" . $this->formatter->format(strtotime($this->data["item_date"])) . "</span>";
        echo "</div>";

        echo "</a>";

    }

    public function setDateFormat(string $dateFormat) : void
    {
        $this->dateFormat = $dateFormat;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setPhotoSize(int $width, int $height): void
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

    protected DBTableBean $bean;

    protected int $selected_year = -1;
    protected int $selected_month = -1;

    protected $columns;

    protected $url;

    protected bool $have_selection = FALSE;

    protected array $selected_ID = array();

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

        $this->url = SparkPage::Instance()->currentURL();

        if ($link_page) {
            $this->url->fromString($link_page);
        }


        $this->selected_ID = array();

        $qry = $this->bean->query(...$this->columns);
        $qry->stmt->order_by = $this->bean->getDateColumn() . " DESC, newsID DESC";
        $qry->stmt->limit = 3;

        $this->itemView = new ItemView($qry);

        $this->itemView->getHeader()->setRenderEnabled(false);
        $this->itemView->getFooter()->setRenderEnabled(false);
        $this->itemView->setItemsPerPage(0);

        $this->itemRenderer = new PublicationItem(get_class($this->bean));
        $this->itemRenderer->getURL()->add(new DataParameter($bean->key()));

        $this->itemView->setItemRenderer($this->itemRenderer);



        $this->items()->append($this->itemView);

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
        $arr[] = Spark::Get(Config::SPARK_LOCAL) . "/css/Publications.css";
        return $arr;
    }

    public function processInput(): void
    {

        $qry = $this->bean->query($this->bean->key(), $this->bean->getDateColumn(), "item_title");
        $qry->stmt->order_by = $this->bean->getDateColumn() . " DESC , newsID DESC ";

        $itemID = -1;
        $year = -1;
        $month = -1;

        if (isset($_GET[$this->bean->key()])) {
            $itemID = (int)$_GET[$this->bean->key()];
            $qry->stmt->where()->add($this->bean->key(), $itemID);
        }
        else if (isset($_GET["year"]) && isset($_GET["month"])) {
            $year = (int)$_GET["year"];
            $month = (int)$_GET["month"];
            $qry->stmt->where()->addExpression("MONTH({$this->bean->getDateColumn()}) = :month");
            $qry->stmt->bind(":month", $month);

            $qry->stmt->where()->addExpression("YEAR({$this->bean->getDateColumn()}) = :year");
            $qry->stmt->bind(":year", $year);
        }

        $num = $qry->count();

        if ($num < 1) {
            $qry->stmt->where()->clear();
            $qry->stmt->limit = 1;
            $itemID = -1;
        }

        $qry->exec();

        $qry->stmt->setMeta("NewsQuery");

        $itemTitle = null;
        while ($item = $qry->next()) {
            if (is_null($itemTitle)) {
                $itemTitle = $item["item_title"];
            }
            $this->selected_ID[] = $item[$this->bean->key()];
            if (!$this->selected_month || $this->selected_year) {
                $this->selected_month = date("n", strtotime($item[$this->bean->getDateColumn()]));
                $this->selected_year = date("Y", strtotime($item[$this->bean->getDateColumn()]));
            }

        }
        if ($itemID < 1) {
            $itemTitle = tr("News Archive: Year: $year Month: $month");
        }
        SparkPage::Instance()->setTitle($itemTitle);

    }

    public function getBean(): DatedBean
    {
        return $this->bean;
    }

    public function renderArchive() : void
    {

        echo "<div class='archive'>";


        $year_list = $this->bean->getYears();

        for ($a = 0; $a < count($year_list); $a++) {

            $year = $year_list[$a];

            echo "<div class='archive_year' >";
            echo "<a onClick='toggleArchiveYear(" . $year . ")'>$year</a>";
            echo "</div>";

            echo "<div class='months' year='$year'>";

            echo "<div class='list'>";

            for ($b = 1; $b <= 12; $b++) {

                $month = self::MonthName($b);

                $have_data = $this->bean->publicationsCount($year, $b);

                if ($have_data > 0) {
                    $url = new URL();
                    $url->copyParametersFrom($this->url);
                    $url->add(new URLParameter("year", $year));
                    $url->add(new URLParameter("month", $b));
                    $active = "";
                    if ( $b == $this->selected_month) {
                        $active = "selected";
                    }
                    echo "<a href='{$url->toString()}' $active class='item'>";
                    echo $month;
                    echo "</a>";
                }
                else {
                    echo "<span class='item'>";
                    echo $month;
                    echo "</span>";
                }


            }

            echo "</div>";
            echo "</div>";
        }
        echo "</div>";

        ?>
        <script type='text/javascript'>

            function toggleArchiveYear(year) {
                document.querySelectorAll(".months[year]").forEach(elm => elm.classList.remove("active"));
                document.querySelector(".months[year='" + year + "']").classList.add("active");
            }

            onPageLoad(function () {
                toggleArchiveYear(<?php echo $this->selected_year;?>);
            });
        </script>

        <?php

    }

    protected function renderImpl(): void
    {
        echo "<div class='Caption'>".tr("Latest News")."</div>";
        parent::renderImpl();

        echo "<div class='Caption'>".tr("News Archive")."</div>";
        $this->renderArchive();
    }

    /**
     * Returns full or abbreviated month name in the desired locale
     *
     * @param int    $monthNumber  1–12
     * @param bool   $short        false = full name, true = abbreviated (3 letters)
     * @return string
     */
    public static function MonthName(int $monthNumber, bool $short = false): string
    {
        if ($monthNumber < 1 || $monthNumber > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12');
        }

        $pattern = $short ? 'MMM' : 'MMMM';

        $formatter = new IntlDateFormatter(
            locale:          Spark::Get(Config::DEFAULT_LOCALE),
            dateType:        IntlDateFormatter::NONE,
            timeType:        IntlDateFormatter::NONE,
            timezone:        'UTC',
            calendar:        IntlDateFormatter::GREGORIAN,
            pattern:         $pattern
        );

        // Most reliable: use DateTime + format('U') → integer timestamp
        $dt = new DateTime();
        $dt->setDate(2000, $monthNumber, 1);
        $dt->setTime(12, 0, 0);           // noon avoids midnight DST issues

        return $formatter->format($dt);
    }
}