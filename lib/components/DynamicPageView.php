<?php
include_once("components/Container.php");

class DynamicPageView extends Container implements IRequestProcessor
{

    protected ?RawResult $result = null;

    /**
     * @var DBTableBean|null
     */
    protected ?DBTableBean $bean = NULL;

    protected bool $processing_done = false;

    public function __construct()
    {
        parent::__construct();

        $heading = new Component();
        $heading->setName("title");

       // $heading->setContents($item["item_title"]);
        $heading->addClassName("title");

        $this->append($heading);

        $contents = new Component();
        $contents->setName("content");

        $contents->addClassName("content");

        //$contents->setContents($item["content"]);

        $this->append($contents);

    }

    public function processInput()
    {

        try {
            if (! ($this->bean instanceof DBTableBean)) throw new Exception("Bean not set yet");

            $id = -1;

            if (isset($_GET["id"]) ) {
                $id = (int)$id;
            }

            //$bean->columns();
            //TODO: check item_title, content, visible is present in this bean
            $query = $this->bean->query("item_title", "content", "visible");
            $query->select->fields()->setExpression("!isNull(photo)", "have_photo");
            $query->select->where()->add($this->bean->key(), $id);
            $query->select->limit = 1;

            $num = $query->exec();
            if ($num < 1) {
                throw new Exception("Page not found");
            }

            if ($this->result = $query->nextResult()) {
                if (!$this->result->get("visible")) throw new Exception("Page is currently unavailable");
            }

            $title_cmp = $this->getByName("title");
            $title_cmp->setContents($this->result->get("item_title"));

            $content_cmp = $this->getByName("content");
            $content_cmp->setContents($this->result->get("content"));

        }
        catch (Exception $e) {
            Session::Set(Session::ALERT, $e->getMessage());
        }

        $this->processing_done = true;

    }

    public function setBean(DBTableBean $bean)
    {
        $this->bean = $bean;
    }

    public function getBean(): DBTableBean
    {
        return $this->bean;
    }

    /**
     * Return true if request data has loaded into this processor
     * @return bool
     */
    public function isProcessed(): bool
    {
        // TODO: Implement isProcessed() method.
        return $this->processing_done;
    }
}