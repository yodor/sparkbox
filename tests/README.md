SparkBox tests
==============

Regression tests for the core library, plus a way to run read-only smoke tests of a site.

Setup (once)
------------

1. Create an empty database and a user for it — **used for tests only**, every run drops and recreates
   its tables:

   ```sql
   CREATE DATABASE sparkbox_test;
   CREATE USER 'sparkbox_test'@'127.0.0.1' IDENTIFIED BY '...';
   GRANT ALL ON sparkbox_test.* TO 'sparkbox_test'@'127.0.0.1';
   ```

2. Copy `tests/config.php.dist` to `tests/config.php` (ignored by git) and fill in the credentials.
   `php_cgi` is the php-cgi binary used by the HTTP level tests (storage requests, site pages);
   leave it empty to skip those tests.

Running
-------

```
php tests/run.php              all core tests
php tests/run.php storage      only files with 'storage' in the name
php tests/run.php --dir=PATH   the *.test.php files of another folder (site tests)
```

Every test file runs in its own PHP process. A test fails on a failed assertion, an exception, or any
unexpected output (warnings, deprecations, fatal errors). Exit code 0 means everything passed.

| File | Covers |
|---|---|
| `core/transactions.test.php` | nested transactions, savepoints, pool and transaction connection, `onCommit`, session time zone |
| `core/storage_versions.test.php` | `StorageVersion` markers after commit, rollback, blob-only tables |
| `core/sql_builder.test.php` | late lookup order, clause identity (ranges), clone isolation, join detection |
| `core/files_cache.test.php` | atomic cache writes under concurrent reads, `SparkFile` path rules |
| `core/storage_http.test.php` | `storage.php` end to end: versions, 304, 301, slug URLs, file names, class validation |

The HTTP tests run `tests/app/*.php` through php-cgi and the real `sparkbox.php` bootstrap.

Writing tests
-------------

```php
<?php
include_once("class/beans/ContactsBean.php");   // tests/app is on the include path

test("what it checks", function () {
    $id = (new ContactsBean())->insert(array("fullname" => "a"));
    assertTrue($id > 0, "no id");
    assertSame(1, (int)TestPDO()->query("SELECT COUNT(*) FROM contacts WHERE crID=$id")->fetchColumn());
    assertThrows(fn() => (new ContactsBean())->getByID(-1));
});
```

`TestPDO()` is a plain PDO connection for checks that must not go through the library. Tables are
defined in `tests/schema.sql`; beans for them in `tests/app/class/beans/`.

Site tests
----------

A site keeps its own `tests/` folder with `*.test.php` files and runs them with the core runner:

```
cd /path/to/site
php /path/to/sparkbox/tests/run.php --dir=tests
```

Site tests should be read-only — they run against the local development database. They request
pages through php-cgi with the site document root, so the site `.user.ini` provides `include_path` and
`auto_prepend_file`. Where `.user.ini` is not read, set `SPARK_SITE_INCLUDE_PATH` and `SPARK_SITE_PREPEND`.
