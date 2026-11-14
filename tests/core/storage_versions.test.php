<?php
/**
 * StorageVersion markers: moved after commit, only for tables with blob columns, never for rolled back writes.
 */
include_once("storage/StorageVersion.php");
include_once("class/beans/PhotosBean.php");
include_once("class/beans/ContactsBean.php");

function markerTime(string $class, int $id) : int|false
{
    clearstatcache();
    return @filemtime(Spark::Get(Config::CACHE_PATH) . "/$class/$id.v");
}

test("marker is written after commit, not before", function () {
    $db = DBManager::Driver();
    $db->transaction();
    $id = (new PhotosBean())->insert(array("photo" => "x", "caption" => "a"), $db);
    assertSame(false, markerTime("PhotosBean", $id), "written before commit");
    $db->commit();
    assertTrue(markerTime("PhotosBean", $id) !== false, "missing after commit");
});

test("commit deletes the bean cache folder", function () {
    $bean = new PhotosBean();
    $id = $bean->insert(array("photo" => "x"));
    $folder = Spark::Get(Config::CACHE_PATH) . "/PhotosBean/$id";
    @mkdir($folder, 0777, true);
    file_put_contents("$folder/photo-0-0", "old");
    $bean->update($id, array("caption" => "b"));
    assertTrue(!is_dir($folder));
});

test("every update moves the marker forward, also within one second", function () {
    $bean = new PhotosBean();
    $id = $bean->insert(array("photo" => "x"));
    $first = markerTime("PhotosBean", $id);
    $bean->update($id, array("caption" => "c"));
    assertTrue(markerTime("PhotosBean", $id) > $first);
});

test("rolled back write does not move the marker", function () {
    $bean = new PhotosBean();
    $id = $bean->insert(array("photo" => "x"));
    $before = markerTime("PhotosBean", $id);
    $db = DBManager::Driver();
    $db->transaction();
    $bean->update($id, array("caption" => "d"), $db);
    $db->rollback();
    assertSame($before, markerTime("PhotosBean", $id));
});

test("no markers for tables without blob columns", function () {
    $id = (new ContactsBean())->insert(array("fullname" => "z"));
    assertSame(false, markerTime("ContactsBean", $id));
});

test("time() creates a missing marker, token() is base36 of it", function () {
    $version = new StorageVersion("PhotosBean", 999);
    $time = $version->time();
    assertTrue($time > 0);
    assertSame(base_convert((string)$time, 10, 36), $version->token(false));
    assertTrue($version->matches($version->token(false), false));
});

test("invalid class names and ids are rejected", function () {
    assertThrows(fn() => new StorageVersion("../x", 1));
    assertThrows(fn() => new StorageVersion("PhotosBean", 0));
    assertTrue(!StorageVersion::ValidToken("../x"));
    assertTrue(StorageVersion::ValidToken("toee7c.ab12"));
});
