<?php
//update.php?class=DocsBean&id=1&content=... - replaces the blob through the bean (markers, cache cleanup)
include_once("class/beans/" . basename((string)$_GET["class"]) . ".php");
include_once("storage/FileStorageObject.php");
$object = new FileStorageObject();
$object->setFilename("a.txt");
$object->buffer()->setData((string)$_GET["content"]);
$className = (string)$_GET["class"];
(new $className())->update((int)$_GET["id"], array("data" => serialize($object)));
echo "updated";
