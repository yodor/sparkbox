<?php
include_once("store/utils/TemplateFactoryEvent.php");

class TemplateFactory
{

    protected static array $templateLocations = array();

    public static function AddTemplateLocation(string $locationPath) : void
    {
        self::$templateLocations[$locationPath] = true;
    }

    /**
     * Try to include the given $templateClass name from the templateLocations
     * if $template is set and instance of PageTemplate call its render() method or throw exception
     * @param string $templateClass
     * @return void
     * @throws Exception
     */
    public static function RenderPage(string $templateClass): void
    {

        foreach (self::$templateLocations as $location=>$enabled) {
            $includeFile = $location."/".$templateClass.".php";
            if (stream_resolve_include_path($includeFile)) {
                include_once($includeFile);
                if (isset($template) && $template instanceof PageTemplate) {
                    break;
                }
            }
        }

        if (isset($template)) {
            SparkEventManager::emit(new TemplateFactoryEvent(TemplateFactoryEvent::TEMPLATE_CREATED, $template));
            $template->render();
            SparkEventManager::emit(new TemplateFactoryEvent(TemplateFactoryEvent::TEMPLATE_RENDERED, $template));
        }
        else {
            throw new Exception("Unable to load template or template variable is not defined correctly");
        }
    }

    public static function MenuForPage(string $menuDefineClass): array
    {
        foreach (self::$templateLocations as $location=>$enabled) {
            $includeFile = $location."/menus/".$menuDefineClass.".php";
            if (stream_resolve_include_path($includeFile)) {
                include_once($includeFile);
                if (isset($menu)) {
                    break;
                }
            }
        }

        if (isset($menu) && is_array($menu)) {
            return $menu;
        }
        Debug::ErrorLog("Menu can not be loaded for this path returning empty menu");
        return array();
    }

}