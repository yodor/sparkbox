<?php
/**
 * Stored objects: the current format round trips, the format before 'Serial' is refused (tools/storage_upgrade.php)
 */
include_once("storage/FileStorageObject.php");

test("a file storage object round trips its data, name and time", function () {
    $file = new FileStorageObject();
    $file->setData("bytes");
    $file->setFilename("a.png");
    $file->setTimestamp(1389119502);
    $copy = unserialize(serialize($file));
    assertSame("bytes", $copy->data());
    assertSame("a.png", $copy->getFilename());
    assertSame(1389119502, $copy->timestamp());
});

test("the format before 'Serial' is refused with the name of the upgrade tool", function () {
    $old = "O:17:\"FileStorageObject\":3:{s:7:\"\0*\0data\";s:3:\"abc\";s:12:\"\0*\0timestamp\";s:19:\"2014-01-07 20:31:42\";s:6:\"\0*\0uid\";s:1:\"u\";}";
    assertThrows(function () use ($old) { unserialize($old); }, "storage_upgrade");
});
