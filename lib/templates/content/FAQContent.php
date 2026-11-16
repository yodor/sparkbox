<?php
include_once("templates/TemplateContent.php");
include_once("beans/FAQSectionsBean.php");
include_once("beans/FAQItemsBean.php");
include_once("utils/url/FAQSectionURL.php");
include_once("utils/script/LDJsonScript.php");
include_once("objects/data/LinkedData.php");

/**
 * Frequently asked questions:
 *  - home (/faq/): every section with its questions - the old ?secID= → 301 to the section address
 *  - section (?fqsID= - /faq/<fqsID>/<slug>/): the questions of one section
 * The sections menu lists the sections that have questions. A question opens its answer (details / summary - no
 * script). FAQPage linked data of the questions shown.
 * Markup: 'column side faq_sections' > Caption, 'menu_links' > a.item; 'column qa' > Caption, 'faq_section' >
 * h2 'section_name', details.item > summary.question, div.answer - styled by the layer or the site (FAQ.css).
 */
class FAQContent extends TemplateContent
{
    protected ?FAQSectionsBean $sections = null;
    protected ?FAQItemsBean $items = null;

    /**
     * Questions of the page for the FAQPage linked data (one per page)
     * @var array
     */
    protected array $entities = array();

    public function initialize() : void
    {
        SparkPage::Instance()->head()->addCSS(Spark::Get(Config::SPARK_LOCAL) . "/css/FAQ.css");

        $this->sections = new FAQSectionsBean();
        $this->items = new FAQItemsBean();
        $key = $this->sections->key();
        $page = SparkPage::Instance();

        //sections with questions, in their order
        $list = array();
        $query = $this->sections->query($key, "section_name");
        $query->stmt->where()->expression("EXISTS (SELECT 1 FROM faq_items fi WHERE fi.$key = faq_sections.$key)");
        $query->stmt->order($key, OrderDirection::ASC);
        $query->exec();
        while ($row = $query->nextResult()) {
            $list[(int)$row->get($key)] = (string)$row->get("section_name");
        }
        $query->free();

        $selected = -1;
        if (strcmp($this->config->filename, "section") === 0) {
            $selected = (int)($_GET[$key] ?? 0);
            if (!isset($list[$selected])) {
                Session::SetAlert(tr("Section not found"));
                header("Location: " . Spark::Get(Config::LOCAL) . FAQSectionURL::$urlFolder);
                exit;
            }
            $url = FAQSectionURL::Section($selected, $list[$selected]);
            $page->redirectCanonical($url);
            $this->setPageURL($url, array($key, "section_name"));
            $this->config->title = $list[$selected] . " - " . tr("Frequently Asked Questions");
        }
        else {
            //old address: ?secID=
            $old = (int)($_GET["secID"] ?? $_GET[$key] ?? 0);
            if ($old > 0 && isset($list[$old])) {
                header("Location: " . FAQSectionURL::Section($old, $list[$old])->toString(), true, 301);
                exit;
            }
            $this->config->title = tr("Frequently Asked Questions");
        }

        $this->cmp = new Container(false);
        $this->cmp->items()->append($this->createMenu($list, $selected));

        $qa = new Container(false);
        $qa->setComponentClass("column qa");
        $caption = new Component(false);
        $caption->setComponentClass("Caption");
        $caption->setText(tr("Frequently Asked Questions"));
        $qa->items()->append($caption);

        $questions = array();
        foreach ($list as $fqsID => $name) {
            if ($selected > 0 && $fqsID != $selected) continue;
            $qa->items()->append($this->createSection($fqsID, $name, $questions));
        }
        $this->cmp->items()->append($qa);

        if (count($this->entities) > 0) {
            $data = new LinkedData("FAQPage");
            $data->setArray("mainEntity", ...$this->entities);
            $script = new LDJsonScript();
            $script->setLinkedData($data);
            $page->head()->addScript($script);
        }

        if (count($questions) > 0) {
            $page->setMetaDescription(Spark::MetaDescription(implode(" ", array_slice($questions, 0, 5)), 150));
        }
    }

    /**
     * The sections menu
     */
    protected function createMenu(array $list, int $selected) : Container
    {
        $column = new Container(false);
        $column->setComponentClass("column side faq_sections");

        $caption = new Component(false);
        $caption->setComponentClass("Caption");
        $caption->setText(tr("Sections"));
        $column->items()->append($caption);

        $links = new Container(false);
        $links->setComponentClass("menu_links");
        foreach ($list as $fqsID => $name) {
            $item = new Component(false);
            $item->setTagName("a");
            $item->setComponentClass("item");
            $item->setAttribute("href", FAQSectionURL::Section($fqsID, $name)->toString());
            $item->setText($name);
            if ($fqsID == $selected) {
                $item->addClassName("selected");
                $item->setAttribute("aria-current", "page");
            }
            $links->items()->append($item);
        }
        $column->items()->append($links);
        return $column;
    }

    /**
     * One section with its questions - their text is added to '$questions' (description)
     */
    protected function createSection(int $fqsID, string $name, array &$questions) : Container
    {
        $section = new Container(false);
        $section->setTagName("section");
        $section->setComponentClass("faq_section");

        $heading = new Component(false);
        $heading->setTagName("h2");
        $heading->setComponentClass("section_name");
        $heading->setText($name);
        $section->items()->append($heading);

        $query = $this->items->query($this->items->key(), "question", "answer");
        $query->stmt->where()->match($this->sections->key(), $fqsID);
        $query->stmt->order($this->items->key(), OrderDirection::ASC);
        $query->exec();
        while ($row = $query->nextResult()) {
            $question = (string)$row->get("question");
            $answer = (string)$row->get("answer");
            $questions[] = $question;

            $item = new Container(false);
            $item->setTagName("details");
            $item->setComponentClass("item");

            $summary = new Component(false);
            $summary->setTagName("summary");
            $summary->setComponentClass("question");
            $summary->setText($question);
            $item->items()->append($summary);

            $content = new Component(false);
            $content->setComponentClass("answer");
            //the answer is the HTML of the admin editor
            $content->setContents($answer);
            $item->items()->append($content);

            $section->items()->append($item);

            $this->entities[] = array("@type" => "Question", "name" => $question,
                "acceptedAnswer" => array("@type" => "Answer", "text" => $answer));
        }
        $query->free();
        return $section;
    }

    protected function setPageURL(URL $url, array $canonicalParameters) : void
    {
        $page = SparkPage::Instance();
        if (method_exists($page, "setContentURL")) $page->setContentURL($url);
        $page->head()->addCanonicalParameter(...$canonicalParameters);
    }

    public function component() : Component
    {
        return $this->cmp;
    }

    protected function getContentTitle() : string
    {
        return tr("Frequently Asked Questions");
    }
}
