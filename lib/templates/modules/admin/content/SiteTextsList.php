<?php
include_once("templates/content/BeanList.php");
include_once("beans/SiteTextsBean.php");
include_once("beans/LanguagesBean.php");

/**
 * Site texts (phrases) used with tr(). Page actions: one per language - its translations, and
 * 'Rehash' - converts the old MD5 hash values to Spark::Hash() (xxh3).
 */
class SiteTextsList extends BeanList
{
    const string KEY_REHASH = "rehash";

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
            $action = TemplateContent::CreateAction("translate_" . $row["langID"], mb_strtoupper($row["lang_code"]), "phrases");
            $action->getURL()->add(new URLParameter("langID", (string)$row["langID"]));
            $action->setTooltip(tr("Translations"));
            $collection->append($action);
        }
        $query->free();

        $rehash = TemplateContent::CreateAction(SiteTextsList::KEY_REHASH, tr("Rehash"));
        $rehash->getURL()->add(new URLParameter(SiteTextsList::KEY_REHASH, "1"));
        $rehash->setTooltip("Rehash using xxh3");
        $collection->append($rehash);
    }

    public function processInput(): void
    {
        if (!isset($_GET[SiteTextsList::KEY_REHASH])) {
            parent::processInput();
            return;
        }

        $query = $this->bean->queryFull();
        $query->exec();
        $updates = array();
        while ($result = $query->nextResult()) {
            //only the old 32 character MD5 values
            if (strlen((string)$result->get("hash_value")) != 32) continue;
            $updates[(int)$result->get($this->bean->key())] = (string)$result->get("value");
        }
        $query->free();

        foreach ($updates as $textID => $value) {
            $this->bean->update($textID, array("value" => $value, "hash_value" => Spark::Hash($value)));
        }

        Session::SetAlert("Rehash complete: " . count($updates));
        $url = Module::PathURL("", URL::Current());
        $url->remove(SiteTextsList::KEY_REHASH);
        header("Location: " . $url);
        exit;
    }

    protected function getContentTitle(): string
    {
        return tr("Translator");
    }
}
