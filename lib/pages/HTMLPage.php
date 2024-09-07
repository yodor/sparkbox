<?php
include_once("components/Component.php");
include_once("utils/URL.php");
include_once("pages/HTMLHead.php");
include_once("pages/HTMLBody.php");

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
        echo "<!DOCTYPE html>\n";

        parent::startRender();

        $this->head->render();

        $this->bodyStart();
    }

    public function finishRender()
    {
        $this->bodyEnd();
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
