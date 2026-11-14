<?php
/**
 * SparkList - identity based lookup and removal, indexes stay sequential
 */
include_once("objects/SparkList.php");

function listOf(int $count) : array
{
    $list = new SparkList();
    $items = array();
    for ($i = 0; $i < $count; $i++) {
        $item = new SparkObject();
        $item->setName("item$i");
        $items[] = $item;
        $list->append($item);
    }
    return array($list, $items);
}

test("indexOf finds the instance at its position", function () {
    [$list, $items] = listOf(4);
    assertSame(0, $list->indexOf($items[0]));
    assertSame(3, $list->indexOf($items[3]));
    assertSame(-1, $list->indexOf(new SparkObject()));
    assertSame(-1, $list->indexOf($items[1], 2));
});

test("remove keeps the indexes sequential", function () {
    [$list, $items] = listOf(4);
    $list->remove(1);
    assertSame(array(0, 1, 2), array_keys($list->toArray()));
    assertSame(1, $list->indexOf($items[2]));
});

test("removeObject and removeAll compare instances, not contents", function () {
    [$list, $items] = listOf(3);
    $twin = new SparkObject();
    $twin->setName("item1");
    $list->removeObject($twin);
    assertSame(3, count($list->toArray()), "removed an equal but different object");
    $list->removeObject($items[1]);
    assertSame(array($items[0], $items[2]), $list->toArray());
    $list->append($items[0]);
    $list->removeAll($items[0]);
    assertSame(array($items[2]), $list->toArray());
});
