<?php
//prints the storage URL StorageItem builds: url.php?class=DocsBean&id=1&type=file|image[&slug=1&name=..]
include_once("storage/StorageItem.php");
if (!empty($_GET["slug"])) Spark::Set(Config::STORAGE_ITEM_SLUG, TRUE);
$item = StorageItem::Create((int)$_GET["id"], (string)$_GET["class"]);
if (!empty($_GET["name"])) $item->setName((string)$_GET["name"]);
echo (($_GET["type"] ?? "file") === "image") ? $item->hrefImage(160, 160) : $item->hrefFile();
