<?php
include_once("templates/TemplateContent.php");
include_once("templates/BeanConfig.php");
include_once("templates/IContentActions.php");

/**
 * A content working on a bean (DBTableBean) - its config is a BeanConfig: setup() creates the bean of beanClass
 * (filtered by Template::Condition when useCondition is set - the lists under a parent row). The lists, editors,
 * trees and galleries of the admin (Bean*), ConfigEditor, the publications.
 * The admin frame fills its page actions and filters from it (fillPageActions, fillPageFilters).
 */
abstract class TemplateBeanContent extends TemplateContent implements IContentActions
{
    protected ?DBTableBean $bean = null;

    protected ?SelectQuery $query = null;

    /**
     * Works on a bean - BeanConfig
     * @return string
     */
    public static function ConfigClass() : string
    {
        return BeanConfig::class;
    }

    public function setBean(DBTableBean $bean): void
    {
        $this->bean = $bean;

        //match reference bean from condition if set
        if (Template::Condition() && $this->config instanceof BeanConfig && $this->config->useCondition) {
            //copy keyID
            $keyID = Template::Condition()->getID();
            $keyName = Template::Condition()->getBean()->key();

            Debug::ErrorLog("Using Template::Condition $keyName [$keyID] to match bean data");

            $this->bean->select()->where()->match($keyName , $keyID);
        }
    }

    public function getBean(): ?DBTableBean
    {
        return $this->bean;
    }

    /**
     * The config of this content: the bean of beanClass; the default title names the bean
     * @param TemplateConfig $config
     * @return void
     * @throws Exception
     */
    public function setup(TemplateConfig $config): void
    {
        $this->config = $config;

        $bean = ($config instanceof BeanConfig) ? $config->createBean() : null;
        if ($bean instanceof DBTableBean) $this->setBean($bean);

        if (!$config->title) {
            $config->title = $this->getContentTitle();
            if (!is_null($this->bean)) $config->title .= ": " . get_class($this->bean);
        }
    }

    /**
     * Create new Action object using Template::PathURL and URL::Current as sourceURL for its href.
     * $usePath can be relative or absolute (to the Template::ModuleLocation).
     * Returned url does not contain pagination parameters.
     * @param string $action
     * @param string|null $contents
     * @param string $usePath
     * @return Action
     * @throws Exception
     */
    public static function CreateAction(string $action, ?string $contents = "", string $usePath = ""): Action
    {
        $act = new Action();
        $act->setURL(Module::PathURL($usePath, URL::Current()));
        $act->getURL()->setClearPageParams(true);
        $act->setAction($action);
        if (!is_null($contents)) {
            $act->setContents($contents);
        }

        return $act;
    }

    /**
     * The page actions of this content (the admin frame: add, back ...)
     * @param ActionCollection $collection
     * @return void
     */
    public function fillPageActions(ActionCollection $collection): void
    {

    }

    /**
     * The page filters of this content (the admin frame: search, filters)
     * @param Container $filters
     * @return void
     */
    public function fillPageFilters(Container $filters): void
    {

    }
}
