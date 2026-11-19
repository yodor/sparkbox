<?php
include_once("templates/content/BeanList.php");
include_once("beans/SiteTextsBean.php");
include_once("beans/LanguagesBean.php");

/**
 * Site texts (phrases) used with tr(). Page actions: one per language - its translations.
 */
class SiteTextsList extends BeanList
{
    public function setup(TemplateConfig $config): void
    {
        $config->beanClass = SiteTextsBean::class;
        $config->listFields = array("value" => "Phrase", "hash_value" => "Hash");
        $config->searchField = array("value");
        parent::setup($config);
        $this->editEnabled = false;
    }

    public function fillPageActions(ActionCollection $collection): void
    {
        $languages = new LanguagesBean();
        $query = $languages->query("langID", "lang_code");
        $query->exec();
        while ($row = $query->next()) {
            $action = TemplateBeanContent::CreateAction("translate_" . $row["langID"], mb_strtoupper($row["lang_code"]), "phrases");
            $action->getURL()->add(new URLParameter("langID", (string)$row["langID"]));
            $action->setTooltip(tr("Translations"));
            $collection->append($action);
        }
        $query->free();
    }

    protected function getContentTitle(): string
    {
        return tr("Translator");
    }
}
