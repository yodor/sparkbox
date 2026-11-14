<?php
/**
 * SQL builder: late lookup order, clause identity, clone isolation, join detection.
 */
include_once("sql/SQLSelect.php");
include_once("iterators/SelectQuery.php");

function itemPrices(SQLSelect $select, bool $lateLookup) : array
{
    $query = new SelectQuery($select, "id", "items");
    $query->execLateLookup = $lateLookup;
    $query->exec();
    $prices = array();
    while ($row = $query->next()) $prices[] = (int)$row["price"];
    return $prices;
}

function pricesSelect(int $limit = 0, int $offset = 0) : SQLSelect
{
    $select = SQLSelect::Table("items");
    $select->columns("items.price", "items.name", "items.body");
    $select->order("items.price", OrderDirection::DESC);
    if ($limit > 0) $select->limit($limit, $offset);
    return $select;
}

test("late lookup keeps the order of a page", function () {
    foreach (array(0, 10, 50) as $offset) {
        assertSame(itemPrices(pricesSelect(10, $offset), false), itemPrices(pricesSelect(10, $offset), true), "offset $offset");
    }
});

test("late lookup keeps the order without a limit", function () {
    assertSame(itemPrices(pricesSelect(), false), itemPrices(pricesSelect(), true));
});

test("late lookup does not change the original select", function () {
    $select = pricesSelect(5);
    $before = $select->getSQL();
    $select->lateLookup("items", "id");
    assertSame($before, $select->getSQL());
});

test("two conditions on one column are both kept (range)", function () {
    $select = pricesSelect();
    $select->where()->match("items.price", 10, ">=");
    $select->where()->match("items.price", 20, "<=");
    $prices = itemPrices($select, false);
    assertTrue(count($prices) > 0, "no rows");
    assertTrue(min($prices) >= 10 && max($prices) <= 20, "range not applied: " . min($prices) . ".." . max($prices));
});

test("same column and operator again replaces the condition", function () {
    $select = pricesSelect();
    $select->where()->match("items.price", 10, ">=");
    $select->where()->match("items.price", 90, ">=");
    $prices = itemPrices($select, false);
    assertTrue(min($prices) >= 90);
});

test("combine on a clone does not touch the original HAVING", function () {
    $select = pricesSelect();
    $clone = clone $select;
    $other = pricesSelect();
    $other->having("COUNT(*) > 1");
    $clone->combine($other);
    assertSame("", (string)$select->having(""));
});

test("copied columns are not shared between statements", function () {
    $a = SQLSelect::Table("items");
    $a->columns("items.price");
    $b = new SQLSelect();
    $a->columns()->copyTo($b->columns());
    $b->columns()->get("items.price")->setAlias("p");
    assertTrue(!str_contains($a->getSQL(), " AS p"), "alias leaked into the original");
});

test("combine appends join expressions of every join type", function () {
    foreach (array("STRAIGHT_JOIN cats ON cats.catID = items.catID", "LEFT OUTER JOIN cats ON cats.catID = items.catID") as $join) {
        $select = SQLSelect::Table("items");
        $select->columns("items.id");
        $other = new SQLSelect();
        $other->from($join);
        $select->combine($other);
        assertTrue(!str_contains($select->getSQL(), ", " . explode(" ", $join)[0]), "comma before: $join");
    }
});
