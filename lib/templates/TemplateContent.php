<?php
include_once("objects/SparkObject.php");
include_once("utils/IRequestProcessor.php");
include_once("templates/TemplateConfig.php");

/**
 * The content of a module path - the inner part of the frame (or the whole answer of a module without a frame).
 * Template::LoadContent(): setup() (the config of the path file - the context), initialize() (the component),
 * processInput(); the frame calls finalizeHead() when its head is final.
 * A content working on a bean (lists, editors, row editors of a bean) is a TemplateBeanContent.
 */
abstract class TemplateContent extends SparkObject implements IRequestProcessor
{
    protected ?TemplateConfig $config = null;
    protected ?Component $cmp = null;

    protected bool $inputProcessed = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function component(): Component
    {
        return $this->cmp;
    }

    /**
     * Initialize the main view component
     * @return void
     */
    abstract public function initialize(): void;


    public function processInput(): void
    {
        $this->inputProcessed = true;
    }

    public function isProcessed(): bool
    {
        return $this->inputProcessed;
    }

    /**
     * The page finalized its head (title, description) - the content adds what depends on them
     * (ex. the prev / next links and the page number of a paged list: AbstractResultView::AppendHeadLinks).
     * Called by SparkPage::startRender() of the frame it is in, after its headFinalize().
     * @param SparkPage $page
     * @return void
     */
    public function finalizeHead(SparkPage $page): void
    {

    }

    /**
     * The config of this content (before initialize()) - the default title when the path file set none
     * @param TemplateConfig $config
     * @return void
     * @throws Exception
     */
    public function setup(TemplateConfig $config): void
    {
        $this->config = $config;

        if (!$config->title) {
            $config->title = $this->getContentTitle();
        }
    }

    /**
     * The config of this content for a path file - a new active config of ConfigClass() with this content class.
     * Contents with settings override it with optional parameters and call parent::Configure() first
     * (BeanList::Configure($beanClass), BeanEditor::Configure($beanClass, $formClass), Plain::Configure($title ...)).
     * A path file includes its content class (include_once) before it calls Configure().
     * @return TemplateConfig
     */
    public static function Configure() : TemplateConfig
    {
        $configClass = static::ConfigClass();
        return $configClass::Content(static::class);
    }

    /**
     * The config class of this content - a content with more settings than TemplateConfig names its own
     * (BeanConfig); Template::LoadContent() converts the config of the path to it
     * @return string
     */
    public static function ConfigClass() : string
    {
        return TemplateConfig::class;
    }

    public function config(): TemplateConfig
    {
        return $this->config;
    }

    /**
     * The title of the page when neither the content nor the frame set one (ex. the selected menu item) -
     * none by default
     * @return string
     */
    public function fallbackTitle(): string
    {
        return "";
    }

    protected function getContentTitle(): string
    {
        return "Item";
    }

}
