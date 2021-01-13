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
            $item = new MenuItem(tr("Translations for").": " . $row["lang_code"], "phrases.php?langID=" . $row["langID"]);
            $item->enableTranslation(false);
            $menu[] = $item;
        }

        $this->page->setPageMenu($menu);

        $this->getSearch()->getForm()->setFields(array("value"));
    }

    protected function initPageActions()
    {
        //disable add item
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