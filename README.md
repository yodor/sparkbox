SparkBox
========

PHP object-oriented library for building CMS, web portals and web applications.

SparkBox is the core layer. It is used as is by sites, and as the base of **sparkstore**
(e-commerce layer, separate repository). A site sits on top of one or both:

```
site (APP_PATH)      class/, config/, page scripts, .htaccess
  └─ sparkstore      lib/store — products, cart, checkout, store admin   (module "store/")
      └─ sparkbox    lib — pages, components, forms, DB, SQL, storage, cache
```

Each layer is a separate checkout. A site points to SparkBox with `include_path`, sparkstore is added
to the include path from the site configuration.

Developed using [Kate](https://kate-editor.org/) and [PHPStorm](https://www.jetbrains.com/?from=sparkbox).


Requirements
------------

- PHP 8.3 or newer (typed class constants) — tested with 8.3, 8.4 and 8.5
- Extensions: pdo_mysql, gd, fileinfo, intl (transliteration of slugs), mbstring
- MariaDB or MySQL — load the time zone tables (`mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql mysql`)
  so the DB session follows the configured PHP time zone
- Apache or LiteSpeed with mod_rewrite (and mod_headers for the long caching of versioned CSS / JS)


Setting up a site
-----------------

1. Copy `dist/htaccess.dist` to the document root as `.htaccess`. It holds the storage rewrite, the
   long caching of versioned CSS / JS, and — for PHP running as a server module (Apache mod_php,
   LiteSpeed) — `include_path` (SparkBox `lib`) and `auto_prepend_file` (the site `sparkboot.php`).
   PHP-FPM / CGI servers read these two from `.user.ini` instead: copy `dist/user.ini.dist`.
2. Copy `dist/sparkboot.php.dist` to the document root as `sparkboot.php`. It defines `APP_PATH` and loads
   `sparkbox.php` — every request goes through it.
3. Create the configuration files in `APP_PATH/config/` (all optional except `dbconfig.php` when the
   database is enabled). They are loaded in this order:

| File | Purpose |
|---|---|
| `boot.php` | before anything else — `DEBUG_LEVEL`, request throttling (`dist/boot.php.dist`) |
| `settings.php` | `Spark::Set(Config::...)` values — site title, paths, caches, image scaler, mailer |
| `dbconfig.php` | `DBManager::Add(DBConfig)` connections (`dist/dbconfig.php.dist`) — keep out of git |
| `include.php` | extra include paths (e.g. sparkstore `lib`) |
| `app.php` | site initialisation after SparkBox is ready |

Site classes go to `APP_PATH/class/`, which is added to the include path — site code includes them as
`include_once("class/pages/MyPage.php")`.

Keep secrets (database, SMTP, payment providers) in files listed in `.gitignore`.


How a request works
-------------------

![Anatomy of a request](docs/anatomy-of-a-request.svg)

Every request is short lived: `sparkboot.php` → `sparkbox.php` bootstrap → configuration → either a
**storage request** (images and files, answered from the storage cache and exits) or a **module**: the
module folder's `index.php` runs `Module::Run()`, which authorizes, takes the path file of the requested
path, creates its content in the frame (the page class of the module), processes the input and renders.
The `<head>` is sent before the body is built.

The module engine (layers, init files, path files, contents, frames, the CSS layers) is described in
[docs/module-engine.md](docs/module-engine.md).


Pages and components
--------------------

![Anatomy of rendering](docs/anatomy-of-rendering.svg)

- `Component` — one HTML element. `render()` = `startRender()` (opening tag) → `renderImpl()` (contents)
  → `finishRender()` (closing tag). Override `syncAttrs()` / `finalize()` to map state to attributes
  and structure just before rendering.
- `Container` — a component with child components (`items()`).
- Components announce their CSS/JS with `requiredStyle()` / `requiredScript()`; `HTMLHead` collects
  them when the component is created.
- `AbstractResultView` (`ItemView`, `TableView`) — renders an `IDataIterator` (usually a `SelectQuery`)
  with paging and sorting (`Paginator`). **One** item renderer (`DataIteratorItem`) instance renders
  every row: everything row specific must be set in `setData($row)`.
- Components can be cached as HTML (`setCacheable(true)`, `PAGE_CACHE_ENABLED`).


Forms
-----

![Anatomy of a form](docs/anatomy-of-a-form.svg)

- `InputForm` holds `DataInput`s (created with `DataInputFactory`). Each `DataInput` has an
  `InputProcessor` (load and transact the value), an `IInputValidator` and an `InputField` renderer.
- `BeanFormEditor` edits one row of a `DBTableBean`: GET loads the row and renders the form, POST
  loads, validates, transacts in one DB transaction (`BeanTransactor`) and redirects back in edit mode.
- Values that live in other tables (photos, attributes) use a *transact bean* on the input processor.


Database
--------

- `DBManager::Driver()` returns a pooled `PDODriver` connection (native prepares, unbuffered results,
  `utf8mb4`, session time zone = PHP time zone). A connection streaming a result is busy — the pool
  hands out another one (usually at most two per request).
- Transactions nest: an inner `transaction()` is a savepoint, only the outermost `commit()` commits.
  `rollback()` outside a transaction is ignored. While a transaction is open, the pool returns its
  connection, and bean writes join it.
- `DBDriver::onCommit()` runs code after the outermost commit — used for cache invalidation.
- `DBTableBean` — one table: `getByID()`, `query()`, `insert()`, `update()`, `delete()`.


SQL builder
-----------

`SQLSelect`, `SQLInsert`, `SQLUpdate`, `SQLDelete` build statements from columns, clause collections
(`where()`, `having()`) and joins. Values are always bound — `match("price", 10, ">=")` adds a bound
clause; the same column with the same operator replaces the clause, a different operator adds one.

`SQLSelect::lateLookup($table, $key)` (`SelectQuery::$execLateLookup`) runs the filtering, ordering
and paging on the key only and reads the selected columns for the found rows. The selected columns
must be readable from `$table` — qualify them with the table name.


Storage and caching
-------------------

- Blob columns (images, files) are served by `storage.php` — query style
  `?cmd=image&class=ProductPhotosBean&id=12&width=640&height=640` or slug style
  `/assets/image/ProductPhotosBean/12/640/640/<version>/<name>.12.webp` (`STORAGE_ITEM_SLUG`).
- `StorageItem` builds the URLs. Every URL carries a **version token** that changes only when the row
  is written through its bean (`StorageVersion` marker files next to the bean cache). Responses with
  the current token are cached by browsers for a year (`immutable`), outdated tokens get a 301 to the
  current URL, URLs without a token get one day.
- Generated images and files are cached in `CACHE_PATH/<BeanClass>/<id>/` (`STORAGE_CACHE_ENABLED`);
  writes through the bean clear them after commit.
- Rows with an `auth_context` column are authorized on every request and sent `private, no-cache`.


Modules
-------

Every page of a site belongs to a module: one top-level folder (`/admin`, `/products`, `/news`, the site
root is `main`) with a linear list of path files (`path/store.products.gallery.php`) that configure the
contents of its paths. The core has the admin, main, pages, news and faq modules; the store adds products,
account, checkout and its admin parts; a site adds or patches paths in its `class/templates/modules`.
See [docs/module-engine.md](docs/module-engine.md).


Tests
-----

`tests/` holds regression tests for the core and a runner for site smoke tests — see
[tests/README.md](tests/README.md). Run `php tests/run.php` before pushing.


Diagrams
--------

The diagrams in `docs/` are plain SVG and can be edited in Inkscape. Each has a generator
(`docs/*.gen.py`, Python 3) that rewrites the SVG — easier for moving boxes than editing by hand.
