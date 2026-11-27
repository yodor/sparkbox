<?php
/**
 * Features: the registry (enable order, requirements, the site location), the routes and the files of an enabled
 * feature, a site file replacing a feature file, a feature that is off (its modules not routed, its admin pages not
 * found - a site copy neither), Module::URL(), MenuItemList::insertAfter() - tests/app/features, tests/app/config/include.php
 */
include_once(__DIR__ . "/../harness/http.php");
include_once("features/Features.php");
include_once("templates/Module.php");
include_once("utils/menu/MenuItemList.php");

/**
 * '$address' as the root .htaccess passes it to index.php (the front controller)
 */
function featureRoot(string $address) : array
{
    return httpRequest("index.php", "path=" . rawurlencode($address), array(), $address);
}

/**
 * The admin path '$path' as a logged in administrator
 */
function featureAdmin(string $path) : array
{
    static $cookies = null;
    if (is_null($cookies)) {
        [, , $body] = httpRequest("admin_user.php", "email=admin@feature.test&password=feature-pass", array(), "",
            array("script_file" => realpath(TESTS_PATH . "/harness/admin_user.php")));
        assertTrue(str_contains($body, "CREATED"), "admin user not created: " . substr(strip_tags($body), 0, 200));
        [, $headers, $body] = httpRequest("admin_login.php", "email=admin@feature.test&password=feature-pass", array(), "",
            array("script_file" => realpath(TESTS_PATH . "/harness/admin_login.php")));
        assertTrue(str_contains($body, "LOGGED_IN"), "login failed: " . substr(strip_tags($body), 0, 200));
        $cookies = implode("; ", $headers["set-cookie"]);
    }
    return httpRequest("admin/index.php", "path=" . urlencode($path), array("Cookie" => $cookies), "/admin/index.php?path=" . urlencode($path));
}

/**
 * A feature folder in the test cache: feature.php returning '$code'
 */
function featureFolder(string $name, string $code) : string
{
    $folder = TestCachePath() . "/features/$name";
    @mkdir($folder, 0777, true);
    file_put_contents("$folder/feature.php", "<?php\nreturn $code;\n");
    return $folder;
}

function featureThrows(Closure $code, string $message) : void
{
    try {
        $code();
    }
    catch (Exception $e) {
        assertTrue(str_contains($e->getMessage(), $message), "message: " . $e->getMessage());
        return;
    }
    throw new Exception("no exception - expected '$message'");
}

test("features: registered, enabled in order, the layer and the required features checked", function () {
    Features::Register(featureFolder("base", "new Feature(id: 'base', routes: array('basemod'))"));
    Features::Register(featureFolder("needs", "new Feature(id: 'needs', requires: array('base'), routes: array('nd' => 'needsmod'))"));
    Features::Register(featureFolder("layered", "new Feature(id: 'layered', layer: 'store')"));

    featureThrows(fn() => Features::Enable("nothing"), "'nothing' is not registered");
    featureThrows(fn() => Features::Enable("needs"), "requires the feature 'base'");
    featureThrows(fn() => Features::Enable("layered"), "requires the 'store' layer");
    featureThrows(fn() => Features::Register(featureFolder("bad", "'text'")), "does not return a Feature");
    featureThrows(fn() => new Feature(id: "Bad-Id"), "Feature id");

    Features::Enable("base", "needs");
    assertTrue(Features::Enabled("base") && Features::Enabled("needs") && !Features::Enabled("layered"), "enabled");
    assertSame(array("layered"), array_keys(Features::Disabled()), "disabled");
    assertSame(array("basemod" => "basemod", "nd" => "needsmod"), Features::Routes(), "routes: folder => module");

    //the site location searches first - a feature after it would replace the site files
    SparkLoader::AddLocation(APP_PATH, "class");
    featureThrows(fn() => Features::Enable("layered"), "config/include.php");
});

test("features: Module::URL - a route of the site, a route of a feature, the main module, none", function () {
    Module::AddRoute("admin");
    Module::AddRoute("shop", "checkout");
    assertSame("/shop/cart", Module::URL("checkout", "/cart")?->toString(), "site route");
    //'needsmod' of the feature 'needs' (enabled by the test above) at its folder 'nd'
    assertSame("/nd/orders/details", Module::URL("needsmod", "orders/details")?->toString(), "feature route");
    assertSame("/", Module::URL("main")?->toString(), "main");
    assertSame(null, Module::URL("nosuchmodule"), "not routed");
});

test("features: MenuItemList::insertAfter - after the item of the address, at the end without one", function () {
    $menu = new MenuItemList(array(new MenuItem("Store", "store"), new MenuItem("Content", "content")));
    $menu->insertAfter("store", new MenuItem("Orders", "orders"));
    $menu->insertAfter("missing", new MenuItem("Last", "last"));
    $names = array_map(fn(MenuItem $item) => $item->getName(), $menu->toArray());
    assertSame(array("Store", "Orders", "Content", "Last"), $names);
});

test("features: the module of an enabled feature is routed, a site file replaces its file", function () {
    [$status, , $body] = featureRoot("/ftest/");
    assertSame(200, $status, "feature module");
    assertTrue(str_contains($body, "<title>Feature Home"), "the path file of the feature");
    [$status, , $body] = featureRoot("/ftest/replaced");
    assertSame(200, $status, "replaced");
    assertTrue(str_contains($body, "<title>Site Copy") && !str_contains($body, "Feature Copy"), "the site copy wins");
});

test("features: the module of a feature that is off is not routed", function () {
    [$status, , $body] = featureRoot("/foff");
    assertSame(404, $status, "not routed - the main module answers");
    assertTrue(!str_contains($body, "Off Home"), "its path file not used");
});

test("features: admin - the pages of an enabled feature, not those of a feature that is off (a site copy neither)", function () {
    [$status, , $body] = featureAdmin("/fon");
    assertSame(200, $status, "fon");
    assertTrue(str_contains($body, "Feature Admin"), "the admin page of the feature (the page title is its menu item)");
    assertTrue(str_contains($body, "Feature On"), "its menu item");
    assertTrue(!str_contains($body, "Feature Off"), "the menu item of a disabled path removed");

    [$status, , $body] = featureAdmin("/foff");
    assertSame(404, $status, "foff");
    assertTrue(!str_contains($body, "Off Admin") && !str_contains($body, "Site Off Admin"), "neither copy");
});
