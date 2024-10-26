<?php
include_once("templates/admin/BeanListPage.php");
include_once("beans/LanguagesBean.php");
include_once("beans/SiteTextsBean.php");

class TranslatorPage extends BeanListPage
{
    public function __construct()
    {
        parent::__construct();

        $this->setBean(new SiteTextsBean());

        $this->setListFields(array("value" => "Phrase", "hash_value" => "Hash"));

        $menu = array();

        $tr = new LanguagesBean();
        $qry = $tr->query();
        $qry->select->fields()->set("langID", "lang_code");
        $qry->exec();

        while ($row = $qry->next()) {
            $item = new MenuItem( mb_strtoupper($row["lang_code"]), "phrases.php?langID=" . $row["langID"]);
            $item->enableTranslation(false);
            $menu[] = $item;
        }

        $this->page->setPageMenu($menu);

        $this->getSearch()->getForm()->setColumns(array("value"));
    }

    protected function initPageActions()
    {
        //disable add item
        $url = URL::Current();
        $url->add(new URLParameter("rehash", "1"));
        $action_add = new Action(SparkAdminPage::ACTION_EDIT, $url->toString());
        $action_add->setTooltip("Rehash using xxH3");
        $this->getPage()->getActions()->append($action_add);
    }

    public function processInput()
    {


        $url = URL::Current();
        if (!$url->contains("rehash")) {
            parent::processInput();
            return;
        }

        $query = $this->bean->queryFull();
        $num = $query->exec();
        $count = 0;
        while ($result = $query->nextResult()) {
            $textID = $result->get($this->bean->key());
            $value = $result->get("value");
            $hash_value = $result->get("hash_value");
            if (strlen($hash_value)!=32) {
                continue;
            }
            $update = array("value"=>$query->getDB()->escape($value), "hash_value"=>sparkHash($value));
            if (!$this->bean->update($textID, $update)) throw new Exception("Unable to update hash_value");
            $count++;
        }
        $url->remove("rehash");
        Session::SetAlert("Rehash complete: ".$count);
        header("Location:".$url);
        exit;
    }

    protected function initViewActions(ActionCollection $act)
    {
        parent::initViewActions($act);

        $edit = $act->getByAction("Edit");
        if ($edit) {
            $act->remove($act->indexOf($edit));
        }

    }

}