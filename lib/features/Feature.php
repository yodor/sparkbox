<?php

/**
 * The descriptor of a feature - a part of a deployment the site switches on as a whole (Features::Enable): its own
 * modules, admin pages, mails and texts in a folder laid out like a layer. The folder holds feature.php, which
 * returns the descriptor (data only):
 *
 *   return new Feature(
 *       id: "shop",
 *       title: "Shop",
 *       layer: "store",                           //the layer it needs (a loader location of this prefix)
 *       requires: array(),                        //the features enabled before it
 *       routes: array("checkout", "account"),     //its modules - routed by the folder of their name
 *       admin: array("orders", "clients"),        //the admin paths it owns - not found while it is off
 *   );
 */
class Feature
{
    /**
     * The folder of the feature (feature.php, templates/, mails/, lang/) - set by Features::Register()
     */
    public string $folder = "";

    /**
     * @param array<string> $requires
     * @param array<string|int, string> $routes the module names (folder = name) or folder => module name
     * @param array<string> $admin
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title = "",
        public readonly string $layer = "",
        public readonly array $requires = array(),
        public readonly array $routes = array(),
        public readonly array $admin = array(),
    ) {
        if (!preg_match("/^[a-z][a-z0-9_]*$/", $id)) throw new Exception("Feature id: lowercase letters, digits and '_' - '$id'");
    }

    /**
     * The routes of the feature: folder => module name
     * @return array<string, string>
     */
    public function routeMap() : array
    {
        $result = array();
        foreach ($this->routes as $folder => $name) {
            $result[is_int($folder) ? $name : $folder] = $name;
        }
        return $result;
    }
}
