<?php
include_once("components/Component.php");
include_once("utils/url/URL.php");
include_once("pages/HTMLHead.php");
include_once("pages/HTMLBody.php");

class HTMLPage extends Component
{

    protected ?HTMLHead $head = null;
    protected ?HTMLBody $body = null;

    public function __construct()
    {
        parent::__construct(false);
        $this->setTagName("HTML");
        //no css class
        $this->setComponentClass("");
        $this->setClassName("");

        $this->head = new HTMLHead();
        $this->body = new HTMLBody();

        $this->setAttribute("lang", substr(DEFAULT_LANGUAGE_ISO3, 0,2));

        global $translator;
        if ($translator instanceof Translator) {
            $this->setAttribute("lang", $translator->activeCodeISO2());
        }

        //override automatic css class names
        $this->body->setComponentClass($this->getPageClass());

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

    public function head() : ?HTMLHead
    {
        return $this->head;
    }

    public function body() : ?HTMLBody
    {
        return $this->body;
    }

    public function startRender()
    {
        echo "<!DOCTYPE html>\n";
        parent::startRender(); //<html>
        $this->head->render();
        $this->body->startRender();
    }

    public function finishRender()
    {
        $this->body->finishRender();
        parent::finishRender(); //</html>
    }

}

?>
