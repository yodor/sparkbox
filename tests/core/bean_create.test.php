<?php
/**
 * DBTableBean creates its missing table from the create string - also when the table references a table that
 * does not exist yet (beans created in any order on a fresh install); the foreign key works once both exist.
 */
include_once("beans/DBTableBean.php");

class TestFKChildBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `test_fk_child` (
  `cID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`cID`),
  KEY `pID` (`pID`),
  CONSTRAINT `test_fk_child_ibfk_1` FOREIGN KEY (`pID`) REFERENCES `test_fk_parent` (`pID`) ON DELETE CASCADE
) ENGINE=InnoDB";

    public function __construct()
    {
        parent::__construct("test_fk_child");
    }
}

class TestFKParentBean extends DBTableBean
{
    protected string $createString = "CREATE TABLE `test_fk_parent` (
  `pID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pID`)
) ENGINE=InnoDB";

    public function __construct()
    {
        parent::__construct("test_fk_parent");
    }
}

test("a table referencing a table that does not exist yet is created; the foreign key works after", function () {
    $pdo = TestPDO();
    $pdo->exec("DROP TABLE IF EXISTS test_fk_child");
    $pdo->exec("DROP TABLE IF EXISTS test_fk_parent");

    new TestFKChildBean();
    assertTrue((bool)$pdo->query("SHOW TABLES LIKE 'test_fk_child'")->fetch(), "child table not created");
    new TestFKParentBean();

    //foreign key checks are back on for the connection
    $row = DBManager::Driver()->query(new RawSQLSelect("SELECT @@foreign_key_checks AS c"));
    assertSame(1, (int)$row->fetch()["c"]);
    $row->free();

    $pdo->exec("INSERT INTO test_fk_parent (pID) VALUES (1)");
    $pdo->exec("INSERT INTO test_fk_child (pID) VALUES (1)");
    assertThrows(function () use ($pdo) {
        $pdo->exec("INSERT INTO test_fk_child (pID) VALUES (99)");
    }, "foreign key constraint");
    $pdo->exec("DELETE FROM test_fk_parent WHERE pID = 1");
    assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM test_fk_child")->fetchColumn(), "cascade");

    $pdo->exec("DROP TABLE test_fk_child");
    $pdo->exec("DROP TABLE test_fk_parent");
});
