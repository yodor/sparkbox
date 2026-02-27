<?php
include_once("templates/TemplateConfig.php");
include_once("templates/TemplateContent.php");
include_once("objects/events/TemplateEvent.php");
include_once("objects/SparkObserver.php");

final class Template
{

    static private string $PrefixTemplateContent = "templates/content";

    static private string $PrefixPathConfig = "admin/path";

    static private ?TemplateConfig $config = null;

    private function __construct()
    {

    }

    public static function List(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->beanClass = $beanClass;
        $config->contentClass = BeanList::class;
        return $config;
    }

    public static function Tree(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanTree::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    public static function Gallery(string $beanClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanGallery::class;

        $config->beanClass = $beanClass;
        return $config;
    }

    public static function Editor(string $beanClass, string $formClass): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = BeanEditor::class;

        $config->beanClass = $beanClass;
        $config->formClass = $formClass;
        return $config;
    }

    public static function Plain(string $title, string $summary): TemplateConfig
    {
        $config = new TemplateConfig();
        $config->contentClass = Plain::class;
        $config->title = $title;
        $config->summary = $summary;

        return $config;
    }

    /**
     * Set/Get the active TemplateConfig configuration used to LoadContent
     *
     * @param TemplateConfig|null $config Set $config as the active TemplateConfig
     * @return TemplateConfig|null Return the current active TemplateConfig
     */
    public static function Config(?TemplateConfig $config=null): ?TemplateConfig
    {
        if (!is_null($config)) {
            Template::$config = $config;
            SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONFIG_CHANGED, Template::$config));
        }

        return Template::$config;
    }

    /**
     * Load content using the active TemplateConfig configuration
     * @return TemplateContent
     * @throws Exception
     */
    public static function LoadContent(): TemplateContent
    {

        if (!(Template::$config instanceof TemplateConfig)) {
            throw new Exception("TemplateConfig not initialized yet");
        }

        if (!Template::$config->contentClass) throw new Exception("TemplateConfig contentClass is empty");

        if (!is_null(Template::$config->observer)) {
            SparkEventManager::register(TemplateEvent::class, new SparkObserver(Template::$config->observer));
        }

        $cmp = SparkLoader::Factory(Template::$PrefixTemplateContent)->instance(Template::$config->contentClass, TemplateContent::class);
        if (!$cmp instanceof TemplateContent) throw new Exception("Content class not instance of TemplateContent");
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_CREATED, $cmp));

        $cmp->setup(Template::$config);
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_SETUP, $cmp));

        $cmp->initialize();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INITIALIZED, $cmp));

        $cmp->processInput();
        SparkEventManager::emit(new TemplateEvent(TemplateEvent::CONTENT_INPUT_PROCESSED, $cmp));

        return $cmp;

    }

    /**
     * Search all loader enabled locations using prefix 'admin/path' and include path file that set the active configuration
     * @param string $path
     * @param int|null $editor
     * @return void
     * @throws Exception
     */
    public static function PathConfig(string $path) : void
    {

        $path = Spark::Split($path, "/");
        $path = implode(".", $path);

        //search all include paths for code to call Template::Configure
        SparkLoader::Factory(Template::$PrefixPathConfig)->include($path);

        if (!(Template::$config instanceof TemplateConfig)) {
            throw new Exception("TemplateConfig not initialized after searching [admin/path] locations for [$path.php]");
        }

        Template::$config->filename = $path;



    }
}