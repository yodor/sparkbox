<?php
include_once("templates/TemplateConfig.php");

/**
 * The config of a content working on a bean (DBTableBean): the lists, editors, trees and galleries of the admin,
 * the row editors of the account and checkout, the publications - TemplateContent::setup() creates the bean.
 */
class BeanConfig extends TemplateConfig
{

    /**
     * DBTableBean class name for BeanList and BeanEditor
     * @var string
     */
    public string $beanClass = "";

    /**
     * InputForm class name for BeanEditor
     * @var string
     */
    public string $formClass = "";

    /**
     * ConfigBean section edited by ConfigEditor
     * @var string
     */
    public string $configSection = "";

    /**
     * Set the KeywordSearch columns
     * @var array|null
     */
    public ?array $searchField = null;

    /**
     * Used in TableView to set the visible columns
     * @var array|null
     */
    public ?array $listFields = null;

    /**
     * @var SelectQuery|null
     */
    public ?SelectQuery $iterator = null;

    /**
     * Using Template::Condition to filter bean data of BeanEditor/GalleryView
     *
     * Signal TemplateContent to use Template::Condition data during setup() call to add match() on the default bean->select,
     * by doing so subsequent query() calls of the bean would return filtered data for the iterators
     *
     * Signal BeanEditor to use Template::Condition data during initialize() call to assign insert value to the transactor
     * @var bool
     */
    public bool $useCondition = false;

    /**
     * A config of '$contentClass' on the bean '$beanClass'
     * @param string $contentClass
     * @param string $beanClass
     * @return static
     */
    public static function Content(string $contentClass, string $beanClass = "") : static
    {
        $config = parent::Content($contentClass);
        $config->beanClass = $beanClass;
        return $config;
    }
}
