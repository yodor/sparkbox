<?php
include_once("features/Feature.php");

/**
 * The features of the deployment. A layer registers the features it ships (sparkstore.php: shop, fast_order), the
 * site enables the ones it has - in config/include.php, after its layers:
 *
 *   include_once("sparkstore.php");
 *   Features::Enable("shop", "fast_order");
 *
 * An enabled feature is one more loader location, after the layers and before the site: its module files, admin
 * pages, menu.php, templates/init.php, mails and lang packs are found like those of a layer, and the site replaces
 * any of them with its own copy. A feature adds files - it does not replace the files of another feature.
 * Its modules are routed only while it is enabled (Module::Routes()); its admin paths are not found while it is
 * registered but off (the admin init.php).
 */
class Features
{
    /**
     * @var array<string, Feature> registered features by id
     */
    protected static array $registered = array();

    /**
     * @var array<string, bool> enabled feature ids, in their order
     */
    protected static array $enabled = array();

    private function __construct()
    {
    }

    /**
     * Register the feature of the folder '$folder' (it holds feature.php)
     * @throws Exception no feature.php, not a Feature, a second feature with this id
     */
    public static function Register(string $folder) : Feature
    {
        $folder = rtrim($folder, "/");
        $file = $folder . "/feature.php";
        if (!is_file($file)) throw new Exception("Feature: no feature.php in '$folder'");
        $feature = include($file);
        if (!($feature instanceof Feature)) throw new Exception("Feature: '$file' does not return a Feature");
        if (isset(Features::$registered[$feature->id]) && Features::$registered[$feature->id]->folder !== $folder) {
            throw new Exception("Feature '{$feature->id}' is registered already");
        }
        $feature->folder = $folder;
        Features::$registered[$feature->id] = $feature;
        return $feature;
    }

    /**
     * Enable the features '$ids' in this order (a feature after the ones it requires) - from config/include.php
     * @throws Exception an unknown feature, a missing layer or feature, called after the site location was added
     */
    public static function Enable(string ...$ids) : void
    {
        //the site location searches first - a feature location added after it would replace the site files
        if (SparkLoader::HasPrefix("class")) {
            throw new Exception("Features::Enable(): call it in config/include.php - before the site location");
        }
        foreach ($ids as $id) {
            if (isset(Features::$enabled[$id])) continue;
            $feature = Features::$registered[$id] ?? null;
            if (is_null($feature)) throw new Exception("Feature '$id' is not registered");
            if ($feature->layer !== "" && !SparkLoader::HasPrefix($feature->layer)) {
                throw new Exception("Feature '$id' requires the '{$feature->layer}' layer");
            }
            foreach ($feature->requires as $required) {
                if (!isset(Features::$enabled[$required])) {
                    throw new Exception("Feature '$id' requires the feature '$required' (enabled before it)");
                }
            }
            SparkLoader::AddLocation($feature->folder, "");
            Features::$enabled[$id] = true;
        }
    }

    public static function Enabled(string $id) : bool
    {
        return isset(Features::$enabled[$id]);
    }

    public static function Get(string $id) : ?Feature
    {
        return Features::$registered[$id] ?? null;
    }

    /**
     * The registered features (enabled or not), by id
     * @return array<string, Feature>
     */
    public static function All() : array
    {
        return Features::$registered;
    }

    /**
     * The registered features that are not enabled
     * @return array<string, Feature>
     */
    public static function Disabled() : array
    {
        return array_diff_key(Features::$registered, Features::$enabled);
    }

    /**
     * The routes of the enabled features: folder => module name
     * @return array<string, string>
     */
    public static function Routes() : array
    {
        $result = array();
        foreach (array_keys(Features::$enabled) as $id) {
            $result += Features::$registered[$id]->routeMap();
        }
        return $result;
    }
}
