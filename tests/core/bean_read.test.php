<?php
/**
 * DBTableBean reads: getResult() null when nothing matches, getByID() throws, getValue() one query, the queries
 * are closed (no open result left on the connection)
 */
include_once("class/beans/ContactsBean.php");

test("getResult / getByID / getValue", function () {
    $bean = new ContactsBean();
    $id = $bean->insert(array("fullname" => "Read Test"));
    assertTrue($id > 0, "insert");

    assertSame("Read Test", $bean->getValue($id, "fullname"));
    assertSame(null, $bean->getValue(999999, "fullname"));
    assertSame(null, $bean->getResult($bean->key(), "999999"));
    assertSame("Read Test", $bean->getByID($id)["fullname"]);
    assertThrows(fn() => $bean->getByID(999999), "No such ID");

    //after a not found getByID the connection is usable for the next query
    assertSame("Read Test", $bean->getByID($id, "fullname")["fullname"]);
    $bean->delete($id);
});
