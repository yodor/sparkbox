<?php
include_once("templates/TemplateConfig.php");
include_once("templates/TemplateContent.php");
include_once("objects/events/TemplateEvent.php");
include_once("objects/SparkObserver.php");

final class Template
{
    //TODO: set config location to realpath Spark::Get(Config::ADMIN_LOCAL)
    public static string $ConfigLocation = "path/";

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

    public static function Content(string $path, TemplateConfig $config): TemplateContent
    {

        if ($config->contentClass) {
            Spark::EnableBeanLocation("templates/content/");
            Spark::LoadBeanClass($config->contentClass);
        }

        if (!is_null($config->observer)) {
            SparkEventManager::register(TemplateEvent::class, new SparkObserver($config->observer));
        }

        $cmp = new $config->contentClass();

        if ($cmp instanceof TemplateContent) {
            $cmp->setup($config);
        }
        else {
            throw new Exception("Content class not instance of TemplateContent");
        }

        return $cmp;

    }

    public static function Config(string $path, ?int $editor = null): TemplateConfig
    {
        Debug::ErrorLog("Locating file for path [$path]");

        $config = null;

        $path = Spark::Split($path, "/");
        $path = implode(".", $path);

        $includeFile = Template::$ConfigLocation . $path . ".php";

        if (file_exists($includeFile)) {

            Debug::ErrorLog("Using include file: $includeFile");
            include_once($includeFile);

        } else {
            Debug::ErrorLog("Include file not found: $includeFile");
            throw new Exception("Include[$includeFile] for path [$path] not found");
        }

        if (!($config instanceof TemplateConfig)) {
            throw new Exception("Include[$includeFile] for path [$path] did not initialize TemplateConfig");
        }

        $config->path = $path;

        return $config;
    }
}