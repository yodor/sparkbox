<?php
include_once("components/Component.php");
include_once("utils/url/URL.php");
include_once("pages/HTMLHead.php");
include_once("pages/HTMLBody.php");

class HTMLPage extends Container
{

    protected ?HTMLHead $head = null;
    protected ?HTMLBody $body = null;

    public function __construct()
    {
        parent::__construct(false);
        $this->setTagName("html");
        //no css class
        $this->setComponentClass("");
        $this->setClassName("");

        $this->head = HTMLHead::Instance();
        $this->body = HTMLBody::Instance();

        $translator = Translator::Instance();
        $this->setAttribute("lang", $translator->activeCodeISO2());

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
        //a module request: the module name and its path ('main partners', 'main home', 'account orders_details')
        //instead of the module index.php - the classes the page scripts had before ('partners', 'home')
        if (class_exists("Module", false) && Module::HasActive()) {
            $pname = Module::Active()->getName();
            $path = trim(Module::RequestPath(), "/");
            //the root path: 'home' is the home page of the site (the main module) - the other modules get 'index',
            //as their index.php gave before ('.home' in a site css means the home page, not the products list)
            if ($path === "" || $path === "home") {
                $sname = ($pname === "main") ? "home" : "index";
            }
            else {
                $sname = Module::FormatPath($path, "_", false);
            }
        }
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

    public function startRender(): void
    {
        echo "<!DOCTYPE html>\n";
        parent::startRender(); //<html>
        $this->head->render();
        $this->body->startRender();
    }

    public function finishRender(): void
    {
        $this->body->finishRender();
        parent::finishRender(); //</html>
    }

}