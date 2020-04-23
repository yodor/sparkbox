<?php
include_once("session.php");
include_once("class/pages/DemoPage.php");

$page = new DemoPage();

$page->startRender();

$dbi = DBDriver::Factory(true, false, "mysqli_conn");

include_once("class/beans/ProductsBean.php");
$prods = new ProductsBean();
$num = $prods->startIterator(" WHERE 1 LIMIT 10 ");
echo "Number of results: $num";
while ($prods->fetchNext($row)) {
    echo "<HR>";
    var_dump($row);

}
$page->finishRender();
?>