<?php
include_once("components/Container.php");

class DynamicPageView extends Container
{
    public function __construct()
    {
        parent::__construct();

        //$this->setClassName("DynamicPage");

        if (!isset($_GET["page_id"]) || !isset($_GET["page_class"])) {
            Session::SetAlert("Required parameters 'page_id' and 'page_class' was not found");
            return;
        }

        $page_class = DBConnections::Get()->escape($_GET["page_class"]);
        $page_id = (int)$_GET["page_id"];

        $item = array();

        @include_once("class/beans/$page_class.php");
        if (!class_exists($page_class)) {
            @include_once("beans/$page_class.php");
            if (!class_exists($page_class)) {
                Session::SetAlert("Required class can not be loaded");
                return;
            }
        }

        $bean = new $page_class;

        if (!($bean instanceof DBTableBean)) {
            Session::SetAlert("Incorrect bean class - Expecting DBTableBean");
            return;
        }

        $bean->fields();
        //TODO: check item_title, content, visible is present in this bean

        $prkey = $bean->key();
        $qry = $bean->query();
        $qry->where = " $prkey='$page_id' ";
        $qry->fields = " item_title, content, visible ";
        $qry->limit = 1;
        $num = $qry->exec();
        if ($num < 1) {
            Session::SetAlert("Page not found");
            return;
        }

        if ($item = $qry->next()) {
            if (!$item["visible"]) {
                Session::SetAlert("Page is currently unavailable.");
                return;
            }
        }

        trbean($page_id, "item_title", $item, $bean->getTableName());
        trbean($page_id, "content", $item, $bean->getTableName());

        //$this->setWrapperEnabled(false);

        $this->setAttribute("itemClass", $page_class);
        $this->setAttribute("itemID", $page_id);

        $heading = new Component();
        $heading->setContents($item["item_title"]);
        $heading->setClassName("item_title");

        $this->append($heading);

        $contents = new Component();

        $contents->addClassName("content");

        $contents->setContents($item["content"]);



        $this->append($contents);

    }


}