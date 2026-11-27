The module engine
=================

How a SparkBox site answers its pages: modules, path files, contents and frames. This is the reference
for writing a module, a content, or a site on top of the core and the store layer.

- [Layers and the search](#layers-and-the-search)
- [A module](#a-module)
- [The request, step by step](#the-request-step-by-step)
- [The site init](#the-site-init)
- [The module init.php](#the-module-initphp)
- [Path files](#path-files)
- [Scripts](#scripts)
- [Contents and their config](#contents-and-their-config)
- [Events: patching a path from a later layer](#events-patching-a-path-from-a-later-layer)
- [Frames](#frames)
- [Fragments: a content without its frame](#fragments-a-content-without-its-frame)
- [Errors](#errors)
- [Strict includes](#strict-includes)
- [CSS: layers, tokens, one stylesheet per part](#css-layers-tokens-one-stylesheet-per-part)
- [Adding a module to a site](#adding-a-module-to-a-site)
- [Features](#features)
- [Planned](#planned)


Layers and the search
---------------------

A site stacks up to three layers. Each one is a location of `SparkLoader`, which finds files by a prefix
relative to the location:

| Layer | Location | Loader prefix | Added by |
|---|---|---|---|
| core | `sparkbox/lib` | (none) | `sparkbox.php` |
| store | `sparkstore/lib` | `store` | the site `config/include.php` |
| site | `APP_PATH` | `class` | `sparkbox.php` |

So `templates/modules/news/init.php` is looked up as `sparkbox/lib/templates/modules/news/init.php`,
`sparkstore/lib/store/templates/modules/news/init.php` and `APP_PATH/class/templates/modules/news/init.php`.

Two ways to use the search:

- `SparkLoader::Factory($prefix)->include($name, true)` includes **every** file of that name, core first,
  the site last. Init files, path files and `menu.php` work this way: each layer adds to what the one
  before it did.
- `SparkLoader::Factory($prefix)->find($name)` returns the **most specific** file, site first. Scripts,
  contents, page classes, beans and authenticators work this way: a site file of the same name replaces
  the library one.

`instance($className, $type)` defines the class from `find()` and checks its type. Class names that come
from configuration or requests are validated before any constructor runs.


A module
--------

A module answers one top-level folder of the site (`/admin`, `/products`, `/news`; the site root is the
`main` module). There are no sub-modules. Its parts:

```
config/routes.php (site)                       the modules of the site by folder: Module::AddRoute("admin")
templates/modules/<name>/init.php              the module settings (each layer); addPattern() for the
                                               addresses that carry values
templates/modules/<name>/path/<a.b.c>.php      one file per path: creates the config of its content (each layer)
templates/modules/<name>/content/<Class>.php   the contents of the module (most specific)
templates/modules/<name>/scripts/<path>.php    paths answered by a plain script (most specific)
templates/modules/<name>/menu.php              admin only: the menu (each layer)
```

**The front controller.** Every address that is not a file of the site goes to the root `index.php`
(`sparkbox/modules/main/index.php`, `Module::Route()`). The SparkBox block of the site root `.htaccess`
(`dist/htaccess.dist`, written by `tools/deploy.php`): an address that is not a file or a folder goes to `index.php`, missing static files keep
the plain server 404, and the code folders `class/` and `config/` are refused (the server would run their files
before any rewrite - the engine never sees those requests).

`Module::Route()` reads the site `config/routes.php` (sample: `dist/routes.php.dist`). The first folder of the
address picks the module: with `Module::AddRoute("admin")`, `/admin/settings` is the admin module, path
`/settings`; `Module::AddRoute("manage", "admin")` serves the same module at `/manage/`. Anything else is the
`main` module. A module is public only when it is listed - the core and store modules exist on every site.
The request is then the same as through a module folder of its own (`SCRIPT_NAME` `/admin/index.php`, `?path=`),
and `Module::Run()` takes the location from it. `/admin` answers 301 to `/admin/`, as the server does for a
folder; `/admin/index.php?path=/settings` keeps working.

**Addresses with values.** `$module->addPattern("([0-9]+)(/.*)?", "view", "dpID")` in the module `init.php`:
`/pages/12/about/` is the path `/view` with `dpID=12`. The pattern is a regular expression of the path without
its leading slash, anchored at both ends; its groups become the request parameters in their order. Patterns
apply first in the response, before the redirects.

**Module folders of a site.** A folder of its own in the document root still works: the server serves an existing
folder before the root rewrite. A site that is not on the main module (its root is its own scripts) runs a module
from such a folder: `admin/index.php` with `Module::Run("admin")` and an `.htaccess` that sends the addresses of
the folder to it (`RewriteRule ^(.*)$ index.php?path=/$1 [QSA,L]` for what is not a file or folder). The core
ships no module folders - `modules/main/index.php` is the front controller of the site root.


The request, step by step
-------------------------

`Module::Route()` (the root `index.php`) picks the module, then `Module::Run($name)`:

1. **`initialize()`** sets the active module, runs the **site init** once per request (`templates/init.php`
   of every layer), then the module `init.php` of every layer.
2. **`Module::Response()`**:
   1. **Patterns, redirects**: an address with values (`addPattern`) becomes its path and parameters. An old address of the module (`addRedirect`) answers 301 and stops. Then the trailing
      slash: with `noTrailingSlash`, `/authors/` answers 301 to `/authors`.
   2. **Authorize**: with an `authClass`, the authenticator gives the context. A path that is not public
      redirects to `loginPath` before any path file runs. An authorized request then goes through
      `accessCheck`; denied, it goes to `home` with an alert.
   3. **Early responders**: for an authorized JSON request, a responder whose `EarlyProcessing()` is true is
      created by name (`responders/json/<Name>.php`) and answers before any page exists.
   4. **Script**: a path with a script (see [Scripts](#scripts)) runs it, and the request is done.
   5. **Page**: without a page class the content alone is the answer (see
      [Fragments](#fragments-a-content-without-its-frame)). Otherwise `pageClassFor($path)` gives the page class, which is created through the `pages` loader. The
      module look is applied (`addPageClass`, `addPageCSS`, `addPageMeta`), then `initialize()`.
   6. **Content**: a page that is an `IRequestProcessor` handles the path itself (`processInput()`). Any other
      page is a frame: `loadContent()` includes the path files (`Template::PathConfig`), creates the content
      of the active config (`Template::LoadContent`: setup → initialize → processInput, each one an event),
      and the frame gets it through `setContent()`.
   7. **`RequestController::Respond()`**: the responders created by the page and its content process the
      request (a JSON responder answers and exits, a form redirects), the session alert is added, and the
      session is closed. Nothing writes the session while rendering.
   8. **`render()`**: the head is finalized (title, description, then `finalizeHead()` of the content) and
      flushed up to `<body>` so the browser fetches css and scripts while the body is built.

![Anatomy of a request](anatomy-of-a-request.svg)


The site init
-------------

`templates/init.php` of each layer (core, store, site) runs once per module request, before the module
`init.php`. It runs for every request: framed pages, JSON responders and scripts. It holds the site setup
that does not belong to one module and costs nothing to declare: query classes, default renderers,
discounts, and the store frame options.

Bookshop (`class/templates/init.php`):

```php
Cart::SetDefaultDiscount(new FixedDiscount());
SellableProducts::SetProductsSelect(new BookshopProductsSQL());
ProductsTape::SetDefaultItemRenderer(new BookshopListItem());
SellableItem::SetDefaultDataParser(new BookshopSellableDataParser());

StoreFrame::Stylesheet(Spark::Get(Config::LOCAL) . "/css/store.css");
StoreFrame::HomeAction("Начало", Spark::Get(Config::LOCAL) . "/");
StoreFrame::Disable(StoreFrame::PAGE_FOOTER);
```

Set options here; don't build components. The frame builds its sections only when a page is rendered.


The module init.php
-------------------

Each layer's `init.php` gets `Module::Active()` and sets it up. The core part sets defaults with
`if (!$module->pageClass)`. The store and the site override them, because they run later.

| Setting | Meaning |
|---|---|
| `pageClass` | the frame (or the whole page) of the module paths: `SparkPage`, `StorePage`, `SparkAdminPage`, a site class; empty: no frame (see [Fragments](#fragments-a-content-without-its-frame)) |
| `authClass` | the authenticator; empty means a public module |
| `addPublicPath("login", ...)` | paths reachable without authorization |
| `loginPath`, `loginAlert` | where an unauthorized request goes, and the alert it sees (empty: none, e.g. the checkout cashier) |
| `accessCheck` | `fn(string $path): bool` after authorization (admin: limited roles) |
| `addPagePath($path, $class, $include)` | another page class for a path and the paths below it |
| `addPathAlias($old, $path)` | an old address answered as a module path, no redirect (`details.php` → `details`) |
| `addRedirect($old, $path)` | an old address answers 301 to a module path; the query is kept |
| `addScriptPath($path, $file)` | a path answered by a given script file |
| `noTrailingSlash` | single page modules (`main`): `/contacts`, not `/contacts/` |
| `addPageClass`, `addPageCSS`, `addPageMeta` | the module look: body class, stylesheet, meta of every page |

The products module (store) as an example:

```php
include_once("store/pages/StorePage.php");

$module = Module::Active();
$module->pageClass = StorePage::class;
$module->addPathAlias("details.php", "details");
$module->addRedirect("list.php", "");
$module->addRedirect("promo.php", "promos");
```

Classes are always named with `::class` (the page class, the content class, the authenticator), and the file of
the class is included first: the IDE knows the class and jumps to it, and the strict includes check sees it. A
site gives a module a page class of its own the same way (`include_once("class/pages/MyPage.php")`,
`$module->pageClass = MyPage::class;`).


Path files
----------

A path maps to one file name: `/store/products/gallery` is `path/store.products.gallery.php`, and the
module folder itself is `home.php`. Paths are lower case ASCII letters. Every layer's file of that name is
included, core first. A path file creates the **config** of its content, and the last config created is
the active one (`TemplateConfig::Active()`). No path file means 404 (`NotFound` content, `noindex`).

A path file is a factory call on its content class:

```php
<?php
include_once("class/templates/modules/products/content/BookshopProductList.php");
$config = BookshopProductList::Configure();
```

or the factory with settings:

```php
<?php
//the Автори index - the values of the attribute 'Автор' (store AttributeIndex)
include_once("store/templates/modules/main/content/AttributeIndex.php");
$config = AttributeIndex::Configure();
$config->title = "Автори";
$config->data = array("attribute" => "Автор", "letter" => "Буква", "placeholder" => "Търси автор ...");
$config->requireAuth = false;
```

A later layer's file of the same name either **replaces** the config (it calls a factory again) or
**patches** it: it takes `TemplateConfig::Active()` and changes its settings (`formClass`, `listFields`,
`searchField`, `beanClass`) or adds an observer (see [Events](#events-patching-a-path-from-a-later-layer)).
This is how a site customizes a store admin page without copying it.

`Template::DisablePath("/content/news")` (from `menu.php` or `init.php`) refuses a path and the paths
below it (they answer 404), and the admin menu drops them.

`Template::Condition()` carries the parent row of nested admin lists (a product's photos): a content with
`BeanConfig::$useCondition` filters its bean by it.


Scripts
-------

A path can be answered by a plain script that does all of its own work (headers, output). This is for
files (`sitemap.xml`, `robots.txt`, a logo) and a home that is not a content. The script of a
path is `templates/modules/<module>/scripts/<path>.php` (the path keeps its dots: `sitemap.xml.php`; the
module folder is `home.php`). The most specific one runs, or the file given with `addScriptPath()`. It runs
after the redirects and the authorization, instead of the page.


Contents and their config
-------------------------

A content (`TemplateContent`) is the inner part of a frame: the component of the path and the work behind
it.

```
TemplateContent              config (TemplateConfig), component(), setup → initialize → processInput, finalizeHead
 └─ TemplateBeanContent      a content on a bean - BeanConfig; page actions and filters (IContentActions)
     ├─ BeanList, BeanTree, BeanGallery, BeanEditor, ConfigEditor     (templates/content - shared)
     └─ the module contents: ProductsList, OrderDetails, PublicationsContent ...
```

- **`Configure()`**: the static factory a path file calls. It creates the active config of
  `ConfigClass()` for this content class. A content with settings takes parameters and calls
  `parent::Configure()` first: `BeanList::Configure($beanClass)`, `BeanEditor::Configure($beanClass, $formClass)`,
  `Plain::Configure($title, $description)`. The path file includes the content class before it calls this.
- **`ConfigClass()`**: `TemplateConfig` by default; `BeanConfig` for bean contents. A content with more
  settings declares its own config class. `Template::LoadContent()` converts the active config to it
  (`From()`), so a path file may create a plain one.
- **`setup($config)`** stores the config and gives the default title. Bean contents also create the bean
  of `beanClass`.
- **`initialize()`** builds the component (`$this->cmp`).
- **`processInput()`** handles the request: forms, filters, paging. Responders created here process in
  `RequestController::Respond()`.
- **`finalizeHead($page)`** runs after the frame finalized its head (title, description); a paged list adds
  its prev / next links here.
- **`fallbackTitle()`** is the page title when neither the content nor the frame gave one.

`TemplateConfig` holds the generic settings: `contentClass`, `title`, `description`, `requireAuth`
(default true), `clearNavigation`, `observer`, and `data` (free settings documented by the content, e.g. the
attribute of `AttributeIndex`). `BeanConfig` adds `beanClass`, `formClass`, `configSection`, `listFields`,
`searchField`, `iterator` and `useCondition`.

The content class is looked up in the module's `content/` folder (site, store, core) and then in the shared
`templates/content` (Plain, NotFound, Bean*, ConfigEditor).

A content reads its data through its bean (`DBTableBean`: `getByID()`, `query()`) and the SQL builder. Clean
table relations are what let a bean list or editor work with no extra code: a foreign key column is a
list filter, a child table is a nested path with `useCondition`.


Events: patching a path from a later layer
------------------------------------------

`Template::LoadContent()` emits a `TemplateEvent` around each step of the content:
`CONTENT_CREATED`, `CONTENT_SETUP`, `CONTENT_INITIALIZED`, `CONTENT_INPUT_PROCESSED`. The `observer` closure
of the active config receives them. `TemplateConfigEvent::UPDATE` is emitted once the path files are all
included.

A later layer chains its observer with `TemplateConfig::WrapObserver()`, so the earlier one still runs:

```php
$config = TemplateConfig::Active();
$config->listFields = array("product_name" => "Title", "price" => "Price");
$config->observer = TemplateConfig::WrapObserver(function (TemplateEvent $event) {
    $content = $event->getSource();
    if (!($content instanceof ProductsList)) return;
    if ($event->isEvent(TemplateEvent::CONTENT_SETUP)) {
        $content->getIterator()->stmt->column("p.insert_date");
    }
    else if ($event->isEvent(TemplateEvent::CONTENT_INITIALIZED)) {
        $content->getItemActions()->getActions()->removeByAction("Variants");
    }
}, $config->observer);
```

Assigning `$config->observer` without wrapping replaces the earlier layer's observer. Do this only on
purpose (mobilevrati's brands list replaces the store columns).

In the store and site path files, prefer settings, then observers, then a content subclass, in that order.


Frames
------

A frame is the page class of a module: it holds the parts every page of the module shares, and gets the
content through `setContent()`.

**`SparkPage`** is the plain frame of the core modules: the default head (viewport, `SparkPage.css`, the
core scripts), the title and description, and the content component appended to the body. The body gets
the classes `<PageClass> <module> <path>` (`StorePage products category`; the site home is
`<PageClass> main home`, the root of any other module `<module> index`) and a `path` attribute.

**`StorePage`** (store) is the frame of the store modules. Its sections come from **`StoreFrame`**, which
the site configures in its site init:

```php
StoreFrame::Stylesheet(Spark::Get(Config::LOCAL) . "/css/store.css");        //the site css of the frame
StoreFrame::Header()->setVideo(Spark::Get(Config::LOCAL) . "/images/header_logo");
StoreFrame::Menu()->setCustomerGroup(false);
StoreFrame::Menu()->setSearchPlaceholder("Търси ...");
StoreFrame::Disable(StoreFrame::PAGE_FOOTER);
StoreFrame::HomeAction("Начало", Spark::Get(Config::LOCAL) . "/");            //the first breadcrumb item
StoreFrame::Use(StoreFrame::HEADER, MyHeader::class);                         //a section of its own
```

The sections are `HEADER`, `MENU`, `PAGE_FOOTER`, `FOOTER` and `COOKIES`. Each is a `StoreSection`: its
constructor only takes options, and `fill($content, $page)` builds it when the frame renders. Each has
its stylesheet (`storefront/css/frame/<Section>.css`); a site section adds its own with `stylesheets()`.
`StorePage` also gives the contents the logged client (`getClient()`, `StorePage::ClientContext()`), the
keyword search (`getKeywordSearch()`), the home action and the main section. A content in the store frame
can be a `Container`: its items become the columns of the main section.

**`SparkAdminPage`** (core admin) is the frame of the admin:

- the side pane (`AdminSidePane`: the administrator, logout, the menu);
- the main pane (`AdminMainPane`: page actions and title, filters, the help pane, the content).

A content that implements `IContentActions` fills the page actions and filters. The menu is built by
`menu.php` of every layer: the core creates it and `SparkAdminPage::SetMenu()` stores it; the store and the
site take `SparkAdminPage::Menu()`, extend it and set it again. `LoadMenu()` runs the menu files once per
request: for the page and for the role check of limited administrators. Roles are the English names of
the top-level menu items.

A site that wants another admin (or another storefront) writes its own page class and sets it as the
module `pageClass`. The frames are not extended through hooks.

A page that implements **`IRequestProcessor`** is not a frame: the engine calls its `processInput()`, and
it renders the path itself.


Fragments: a content without its frame
--------------------------------------

The content of any module path can be answered alone, as a fragment, by `ContentFragmentResponder`:

- **A module without a frame** (`pageClass` empty). Every path answers its content with no page around it.
  A plain request gets HTML: the `<link>`s and `<script>`s its components need, the content, then the page
  components (dialog templates, inline scripts). A JSON request gets the same as JSON. The answer is
  `X-Robots-Tag: noindex`, and the module stylesheets (`addPageCSS`) come with it.
- **A framed module, on request**. A JSON request with `responder=ContentFragmentResponder&function=render`
  builds the page as usual (the frame gives the content its context: the client, the search) and answers
  only the content. The HTML page of the same path does not change.

The JSON response:

| Field | What |
|---|---|
| `contents` | the HTML of the content component (one element) |
| `styles` | the stylesheets of the head, in their order (versioned addresses) |
| `scripts` | the script files of the head |
| `page` | the page components: dialog templates, inline scripts |

In the browser, `ContentFragment` (`sparkfront/js/ContentFragment.js`, on every page) loads one:

```js
new ContentFragment("/products/promos/").load(document.querySelector(".promos"))
    .then((element) => { ... });
```

It adds the stylesheets and scripts the page does not have yet (the scripts in their order), appends the
content, adds the page components (a template already in the page is not added again), and notifies
`ContentFragment.EVENT_LOADED`. An unknown path (404) or an error rejects the promise.

A content that can run without a frame adds its css and js through `HTMLHead::Instance()` (it exists without
a page). It reaches the page only through `SparkPage::Instance()?->` (title, description, canonical, og tags),
or `instanceof StorePage` for what the store frame gives. The core contents of news, faq and pages do this.


Errors
------

- An unknown path answers 404 with the `NotFound` content, `noindex`.
- A path file or a content that throws gives the error content of the path (`Plain`, `noindex`). The
  message is shown escaped; with debug enabled, the trace is added.
- A component that throws while rendering is replaced by `span.RenderError` with the message escaped,
  and the error is logged with the class, file and line.
- `display_errors` stays on: a visible error gets fixed.


Strict includes
---------------

Every file includes, with `include_once`, each class it uses with `new`, `::`, `extends` or `implements`.
The include path is the layer's, e.g. `include_once("store/beans/SellableProducts.php")`. A file must not
work only because another file happened to load the class first. Exempt: `instanceof` and `catch` (they
load nothing), and a use guarded by `class_exists("X", false)`. A class defined in more than one place (a
site bean of the same name) is not checked.

`tests/harness/includes.php` checks this for the core and the store (`tests/core/includes.test.php`), and
`php tests/check_includes.php <site root>` checks a site.


CSS: layers, tokens, one stylesheet per part
--------------------------------------------

`HTMLHead` declares the cascade layers first:

```css
@layer base, components, core, store, site;
```

A later layer wins whatever the selector specificity. Every stylesheet wraps its rules in its layer:

| Layer | What |
|---|---|
| `base` | element-only rules of every stylesheet (`body`, `h1`, `img`, `nav ul`): never above a class rule |
| `components` | the core building blocks, one stylesheet per component class (Action, ColorButton, InputField, TableView, MenuBar, the dialogs) |
| `core` | the core frames and module contents (SparkPage, SparkAdminPage and its panes, faq, news) |
| `store` | the store frame and its sections, the store components and modules (`storefront/css`) |
| `site` | the site css |

A frame, a module or a site adjusts a building block with a plain selector; it does not need a more
specific one. `!important` reverses the order between layers (an earlier layer's important wins), so it is
not used to override.

**Theme tokens** (`sparkfront/css/themes/<theme>.css` - `Config::THEME`, default `light` - loaded on every page) are the palette and the font:
`--theme-font-family`, `--theme-text`, `--theme-background`, `--theme-accent`, `--theme-accent-light`,
`--theme-accent-dark`, `--theme-link`, `--theme-surface`, `--theme-error`. The store and the site set them in
their `:root`. **Component tokens** (`--spark-button-*`, `--spark-dialog-*`, `--spark-action-color`) belong to
their component and default to the theme; a site sets one only where that component differs.

There is no `@import`. Each part adds its own stylesheet: a component with `requiredStyle()`, a frame
section with `stylesheets()`, a module with `addPageCSS()`, a content through the page head.
`url()` paths are relative to the stylesheet's own folder.


Adding a module to a site
-------------------------

A site module (the site's own data, e.g. projects) is written in the site's `class/` folder; the core gets
nothing new:

1. **Tables and beans**: tables with clean relations (keys, foreign keys, child tables for photos) and a
   `DBTableBean` per table in `class/beans/`.
2. **Admin**: `class/templates/modules/admin/menu.php` adds the menu items to `SparkAdminPage::Menu()`.
   `class/templates/modules/admin/path/<item>.php` files configure the shared contents:
   `BeanList::Configure()`, `BeanEditor::Configure()` with an `InputForm` in `class/forms/`,
   `BeanGallery` for photos with `useCondition`.
3. **Frontend**: the route of the module (`Module::AddRoute("<name>")` in `config/routes.php`);
   `class/templates/modules/<name>/init.php` (page class, page css, `addPattern()` for the addresses with
   ids); path files for the list and the details; contents in `content/` (a list on an `ItemView` of the
   bean's query, a details view of one row).
4. **SEO**: a table whose rows are pages has the columns `seo_title` and `seo_description`; its form adds
   them with `SEOInputGroup::AddTo($this)`, and the content gives the page `SEOData::Title($row, $name)` (the
   `<title>`) and `SEOData::Description($row, $text)`. The heading of the page keeps the name. The default
   description of the site is Settings > SEO.
5. **Look**: the module stylesheet in the `site` layer, using the theme tokens.
6. **Checks**: `php tests/check_includes.php <site>` and a smoke test of the new paths.

A new module for every site goes into a layer instead: `templates/modules/<name>/` in the core or the
store; each site that serves it adds its route.


Features
--------

A **feature** is a part of a deployment that a site switches on as a whole: its own modules, admin pages,
mails and texts. The store ships `shop` (checkout, account, orders, clients, couriers) and `fast_order`;
the catalog is the store layer itself.

A feature is a folder laid out like a layer, with a `feature.php` returning its descriptor (data only):

```php
return new Feature(
    id: "shop",
    title: "Shop",
    layer: "store",                         // the layer it needs
    requires: array(),                      // the features enabled before it
    routes: array("checkout", "account"),   // its modules, routed at the folder of their name
    admin: array("orders", "clients"),      // the admin paths it owns
);
```

- **Registering.** A layer registers the features it ships (`Features::Register(<folder>)` in `sparkstore.php`).
- **Enabling.** The site enables the features it has in `config/include.php`, after its layers. This must happen
  before the site location is added, so `routes.php` is too late, and `Enable()` refuses to run there:

  ```php
  include_once("sparkstore.php");
  Features::Enable("shop", "fast_order");
  ```

**What enabling does.** `Enable()` checks the layer and the required features (in the order given), adds the
folder as a loader location between the layers and the site, and adds the routes of its modules
(`Module::Routes()`; a route of the site's `routes.php` wins). Nothing else is new: the module `init.php` and path
files, the admin `menu.php`, the site init `templates/init.php`, the mails, the mail previews and the language packs
of the feature are found like those of a layer.

**Overrides.** A site file replaces a feature file the usual way. A feature adds files. It does not replace the
files of another feature; it asks `Features::Enabled("<id>")` instead.

**A feature that is off** is registered but not enabled. Its modules are not routed (404), and its admin paths are
not found even when the site has its own copy of them (`Template::DisablePath()`, which also removes their menu
items).

**Addresses between modules.** `Module::URL("account", "/orders/details")` is the address of a path of another
module, or `null` when the site has no such module. The admin menu of a feature puts its sections next to the
others with `MenuItemList::insertAfter("store", $item)`.


Planned
-------

- **Plugins**: a feature from outside the layers (`plugins/<name>`) that brings its own code - the credit buttons,
  Stripe. Registered and enabled like a built-in feature; its public files linked into the site root by the
  deploy tool. A site's own module is not a plugin.
