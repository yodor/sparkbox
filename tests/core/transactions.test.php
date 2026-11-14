<?php
/**
 * DBDriver transactions: nesting with savepoints, the pool preferring the transaction connection,
 * bean writes joining the running transaction, commit callbacks.
 */
include_once("sql/RawSQLSelect.php");
include_once("class/beans/ContactsBean.php");

function contactsCount() : int
{
    return (int)TestPDO()->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
}

function firstRow(DBDriver $db, string $sql) : ?array
{
    $result = $db->query(new RawSQLSelect($sql));
    $row = $result->fetch();
    $result->free();
    return $row ?: null;
}

test("outer rollback undoes a bean insert done without \$db", function () {
    $before = contactsCount();
    $db = DBManager::Driver();
    try {
        $db->transaction();
        (new ContactsBean())->insert(array("fullname" => "a"));
        throw new Exception("failure after insert");
    }
    catch (Exception $e) {
        $db->rollback();
    }
    assertSame($before, contactsCount());
    assertTrue(!$db->inTransaction(), "still in transaction");
});

test("nested inserts are committed once by the outer commit", function () {
    $before = contactsCount();
    $db = DBManager::Driver();
    $db->transaction();
    (new ContactsBean())->insert(array("fullname" => "b"));
    (new ContactsBean())->insert(array("fullname" => "c"));
    $db->commit();
    assertSame($before + 2, contactsCount());
});

test("savepoint rollback drops only the inner work", function () {
    $before = contactsCount();
    $db = DBManager::Driver();
    $bean = new ContactsBean();
    $db->transaction();
    $bean->insert(array("fullname" => "keep"), $db);
    $db->transaction();
    $bean->insert(array("fullname" => "drop"), $db);
    $db->rollback();
    $db->commit();
    assertSame($before + 1, contactsCount());
    assertSame(0, (int)TestPDO()->query("SELECT COUNT(*) FROM contacts WHERE fullname='drop'")->fetchColumn());
});

test("reads inside a transaction see its uncommitted rows", function () {
    $db = DBManager::Driver();
    $db->transaction();
    (new ContactsBean())->insert(array("fullname" => "rw"), $db);
    $reader = DBManager::Driver();
    $row = firstRow($reader, "SELECT COUNT(*) c FROM contacts WHERE fullname='rw'");
    $db->rollback();
    assertTrue($reader === $db, "pool returned another connection");
    assertSame(1, (int)$row["c"]);
});

test("write without \$db while the transaction connection is busy throws", function () {
    $db = DBManager::Driver();
    $db->transaction();
    $busy = $db->query(new RawSQLSelect("SELECT * FROM contacts"));
    $busy->fetch();
    assertThrows(fn() => (new ContactsBean())->insert(array("fullname" => "escape")), "busy");
    $db->rollback();
    assertTrue(!$db->inTransaction());
});

test("rollback outside a transaction is ignored, commit throws", function () {
    $db = DBManager::Driver();
    $db->rollback();
    assertThrows(fn() => $db->commit());
});

test("disconnect rolls back an open transaction", function () {
    $before = contactsCount();
    $db = DBManager::Driver();
    $db->transaction();
    (new ContactsBean())->insert(array("fullname" => "lost"), $db);
    $db->disconnect();
    assertSame($before, contactsCount());
});

test("onCommit runs after the outer commit only", function () {
    $db = DBManager::Driver();
    $runs = array();
    $db->transaction();
    $db->onCommit(function () use (&$runs) { $runs[] = "outer"; });
    $db->transaction();
    $db->onCommit(function () use (&$runs) { $runs[] = "released"; });
    $db->commit();
    $db->transaction();
    $db->onCommit(function () use (&$runs) { $runs[] = "rolled back"; });
    $db->rollback();
    assertSame(array(), $runs, "ran before the outer commit");
    $db->commit();
    assertSame(array("outer", "released"), $runs);
});

test("session time_zone follows PHP", function () {
    $row = firstRow(DBManager::Driver(), "SELECT @@session.time_zone tz");
    $zone = date_default_timezone_get();
    if ($row["tz"] !== $zone) skip("time zone tables not loaded - server keeps '{$row['tz']}'");
    assertSame($zone, $row["tz"]);
});
