<?php
include_once("components/Component.php");
include_once("utils/url/URL.php");
include_once("pages/HTMLHead.php");
include_once("pages/HTMLBody.php");
include_once("objects/SparkEvent.php");

class HTMLPageEvent extends SparkEvent
{
    const OUTPUT_STARTING = "output_starting";
    const OUTPUT_FINISHING = "output_finishing";

    public function __construct(string $name, SparkObject $source = null)
    {
        parent::__construct($name, $source);
    }
}

class HTMLPage extends Component
{

    protected HTMLHead $head;
    protected HTMLBody $body;

    public function __construct()
    {
        parent::__construct(false);

        $this->tagName = "html";

        $this->head = new HTMLHead();
        $this->body = new HTMLBody();

        $this->setAttribute("lang", substr(DEFAULT_LANGUAGE_ISO3, 0,2));

        //override automatic css class names
        $this->body->setComponentClass($this->getPageClass());

        //no css class
        $this->setClassName("");
        $this->setComponentClass("");

    }

    public function getPageClass() : string
    {
        //global $install_path;
        //$script_name = str_replace($install_path,"", $_SERVER["SCRIPT_FILENAME"]);
        $script_name = $_SERVER["SCRIPT_NAME"];
        $sname = str_replace(".php", "", basename($script_name));
        $pname = basename(dirname($script_name));
        return get_class($this) . " " . $pname . " " . $sname;
    }

    public function head() : HTMLHead
    {
        return $this->head;
    }
    public function body() : HTMLBody
    {
        return $this->body;
    }

    protected function bodyStart()
    {
        $this->body->startRender();
    }

    protected function bodyEnd()
    {
        $this->body->finishRender();
    }

    public function startRender()
    {
        SparkEventManager::emit(new HTMLPageEvent(HTMLPageEvent::OUTPUT_STARTING, $this));
        echo "<!DOCTYPE html>\n";

        parent::startRender();

        $this->head->render();

        $this->bodyStart();
    }

    public function finishRender()
    {
        $this->bodyEnd();
        SparkEventManager::emit(new HTMLPageEvent(HTMLPageEvent::OUTPUT_FINISHING, $this));
        parent::finishRender();

    }

    /**
     * Return the full URL this page is running from
     * @return string
     */
    public function getPageURL() : string
    {
        return currentURL();
    }

    public function getURL(): URL
    {
        return new URL($this->getPageURL());
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}

?>
