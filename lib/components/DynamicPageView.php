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

    protected ?StorageItem $photo = NULL;

    public function __construct()
    {
        parent::__construct();

        $heading = new Component();
        $heading->setName("title");

        $heading->addClassName("title");

        $this->items()->append($heading);

        $contents = new Component();
        $contents->setName("content");

        $contents->addClassName("content");

        $this->items()->append($contents);

    }

    public function processInput(): void
    {

        try {
            if (! ($this->bean instanceof DBTableBean)) throw new Exception("Bean not set yet");

            $id = -1;

            if (isset($_GET["id"])) {
                $id = (int)$_GET["id"];
            } else if (isset($_GET["page_id"])) {
                $id = (int)$_GET["page_id"];
            }

            if ($id < 1) throw new Exception("Page ID not found");

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

            $title_cmp = $this->items()->getByName("title");
            $title_cmp->setContents($this->result->get("item_title"));

            $content_cmp = $this->items()->getByName("content");
            $content_cmp->setContents($this->result->get("content"));

            if ($this->result->get("have_photo")) {
                $this->photo = new StorageItem($this->result->get($this->bean->key()), get_class($this->bean));
            }

            $this->processing_done = true;
        }
        catch (Exception $e) {
            Session::Set(Session::ALERT, $e->getMessage());
        }

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
        return $this->processing_done;
    }

    public function getResult() : ?RawResult
    {
        return $this->result;
    }
    public function getPhoto(): StorageItem
    {
        return $this->photo;
    }
}
