<?php
// define("DEBUG_OUTPUT", 1);
include_once("session.php");


include_once("lib/components/NestedSetTreeView.php");
include_once("lib/components/renderers/items/TextTreeItemRenderer.php");
include_once("class/beans/ProductCategoriesBean.php");
include_once("class/beans/ProductsBean.php");
include_once("class/beans/ProductPhotosBean.php");
include_once("lib/components/TableView.php");
include_once("lib/components/ListView.php");

include_once("lib/components/KeywordSearchComponent.php");
include_once("lib/iterators/SQLQuery.php");
include_once("lib/components/renderers/items/ItemRendererImpl.php");

include_once("class/beans/SellableProductsView.php");

include_once("class/components/renderers/items/ProductListItem.php");

include_once("class/pages/ProductDetailsPage.php");

// function dumpJS()
// {
//   echo "<script type='text/javascript' src='".SITE_ROOT."js/product_details.js'></script>";
//   echo "\n";
// }
// function dumpCSS()
// {
//   echo "<link rel='stylesheet' href='".SITE_ROOT."css/product_details.css' type='text/css'>";
//   echo "\n";
// }

$page = new ProductDetailsPage();

$prodID = -1;
if (isset($_GET["prodID"])) {
    $prodID = (int)$_GET["prodID"];
}
$piID = -1;
if (isset($_GET["piID"])) {
    $piID = (int)$_GET["piID"];
}

$sellable = array();

$db = DBDriver::Get();
$res = NULL;
try {

    $relation = $page->derived;

    $relation->where = " pi.prodID = $prodID ";
    $relation->group_by = " pi.piID ";

    //   echo $relation->getSQL();

    $res = $db->query($relation->getSQL());
    if (!$res) throw new Exception("Product Not Found: " . $db->getError());

    $found_piID = false;
    while ($item = $db->fetch($res)) {
        $sellable[] = $item;
        //
        if ((int)$piID == (int)$item["piID"]) {
            $found_piID = true;
        }

    }
    $db->free($res);

    if (count($sellable) < 1) {
        throw new Exception("Product Not Found.");
    }

    if (!$found_piID) {
        $piID = $sellable[0]["piID"];
    }

}
catch (Exception $e) {


    Session::SetAlert("This product is currently unaccessible. Error: " . $e->getMessage());
    header("Location: list.php");
    exit;
}


//per pclrID items used for color button images
$color_chips = array();

//per pclrID color names
$color_names = array();

//per pclrID items
$galleries = array();

$prices = array();

//per inventory ID
$attributes = array();

$pos = 0;

//product_colors => product color scheme
//product_color_photos => product color scheme photos
foreach ($sellable as $pos => $row) {


    // 	echo $pos++;

    //NULL color links all sizes goes to key 0
    $pclrID = (int)$row["pclrID"];

    $attr_list = explode("|", $row["inventory_attributes"]);
    $attr_all = array();
    foreach ($attr_list as $idx => $pair) {
        $name_value = explode(":", $pair);
        $attr_all[] = array("name" => $name_value[0], "value" => $name_value[1]);
    }

    $attributes[$row["piID"]] = $attr_all;


    $prices[$pclrID][$row["size_value"]][$row["piID"]] = $row["sell_price"];

    $color_names[$pclrID] = $row["color"];

    $color_codes[$pclrID] = $row["color_code"];

    if (!isset($galleries[$pclrID])) {
        $galleries[$pclrID] = array();
        $use_photos = false;

        if ($pclrID > 0) {
            $res = $db->query("SELECT pclrpID FROM product_color_photos WHERE pclrID=$pclrID ORDER BY position ASC");
            if (!$res) throw new Exception("Unable to query color gallery: " . $db->getError());
            if ($db->numRows($res) < 1) $use_photos = true;
            while ($grow = $db->fetch($res)) {
                $item = array("id" => $grow["pclrpID"], "class" => "ProductColorPhotosBean");
                $galleries[$pclrID][] = $item;
            }
            $db->free($res);

        }
        if ($use_photos || $pclrID < 1) {
            //attach default photo as signle color gallery
            $res = $db->query("SELECT ppID FROM product_photos WHERE prodID=$prodID ORDER BY position ASC");
            if (!$res) throw new Exception("Unable to query product gallery: " . $db->getError());
            while ($grow = $db->fetch($res)) {
                $item = array("id" => $grow["ppID"], "class" => "ProductPhotosBean");
                $galleries[$pclrID][] = $item;
            }
            $db->free($res);
        }
    }


    //use the color chip from product color scheme
    if ((int)$row["have_chip"] > 0) {
        $item = array("id" => $pclrID, "class" => "ProductColorsBean&bean_field=color_photo");
        $color_chips[$pclrID] = $item;
    }
    else {
        //no chip assigned - use first image from the gallery if there is atleast one coloring scheme setup
        if (isset($galleries[$pclrID][0])) {
            $color_chips[$pclrID] = $galleries[$pclrID][0];
        }
        else {
            //use the color code as color button
            $item = array("id" => $pclrID);
            $color_chips[$pclrID] = $item;
        }
    }

}


$page->startRender();

// var_dump($attributes);
// print_r($galleries);


$sellable_variation = $sellable[0];
// echo $sel->getSQL();


$page->renderCategoryPath($sellable_variation["catID"]);

echo "<h1>" . $sellable_variation["product_name"] . "</h1>";

echo "<div class='column details'>";

echo "<div class='images'>";

//main image
$gallery_href = StorageItem::Image($itemID, "NewsItemsBean", 400, -1);
$big_href = StorageItem::Image($itemID, "NewsItemsBean");
echo "<div class='image_big' source='$gallery_href' >";
echo "<a class='image_popup' href='' source='$big_href'><img src='$big_href'></a>";
echo "</div>";

//photo galleries per color
echo "<div class='image_gallery'>";
foreach ($galleries as $pclrID => $gallery) {
    echo "<div class='list' pclrID='$pclrID'>";
    foreach ($gallery as $key => $item) {
        $href_source = STORAGE_HREF . "?cmd=image&width=110&height=110";

        $href = $href_source . "&class=" . $item["class"] . "&id=" . $item["id"];

        echo "<div class='item' bean='{$item["class"]}' itemID='{$item["id"]}' source='$href_source' onClick='javascript:changeImage(this)' style='background-image:url($href)'>";
        // 			echo "<img src='$href' >";
        echo "</div>";

    }
    echo "</div>";//list
}
echo "</div>";//image_gallery

echo "</div>"; // images

echo "<div class='product_details'>";

echo "<div class='item sell_price'>";
echo "<label>" . tr("Price") . "</label>";
echo "<span class='value' piID='$piID'>{$sellable_variation["sell_price"]}</span>";
echo "</div>";


//hide color chooser for single color or color schemeless products
// 	if ($pclrID == 0 || count($color_names)==1) {
//             //no colors setup
//             $chooser_visibility = "style='display:none'";
// 	}

echo "<div class='item current_color'>";
echo "<label>" . tr("Color Scheme") . "</label>";
echo "<span class='value'></span>";
echo "</div>";

echo "<div class='item color_chooser'>";
echo "<label>" . tr("Choose Color") . "</label>";
echo "<span class='value'>";

foreach ($color_chips as $pclrID => $item) {
    $pclrID = (int)$pclrID;

    if (isset($item["class"])) {
        $href = StorageItem::Image($item["id"], $item["class"], 48, 48);
    }

    $chip_colorName = $color_names[$pclrID];

    $sizes = $prices[$pclrID];
    $pids = array();
    $sell_prices = array();
    foreach ($sizes as $size_value => $arr) {
        foreach ($arr as $cpiID => $sell_price) {
            $pids[] = $cpiID;
            $sell_prices[] = $sell_price;
        }
    }
    $size_values = implode("|", array_keys($sizes));
    $cpiID = $pids[0];
    $pids = implode("|", $pids);
    $sell_prices = implode("|", $sell_prices);

    //sizing pids = $pid_values
    echo "<span class='color_button' pclrID='$pclrID' piID='$cpiID' size_values='$size_values' sell_prices='$sell_prices' pids='$pids' color_name='$chip_colorName' onClick='javascript:changeColor($pclrID)' title='$chip_colorName'>";

    if (isset($item["class"])) {
        echo "<img src='$href' >";
    }
    else {
        echo "<span class='simple_color' style='background-color:{$color_codes[$pclrID]};'></span>";
    }

    echo "</span>";
}

echo "</span>";//value
echo "</div>";//color_chooser

//default hidden
echo "<div class='item size_chooser'>";
echo "<label>" . tr("Size") . "</label>";
echo "<span class='value'>";
echo "<select class='product_size' onChange='javascript:updatePrice()'>";
echo "</select>";
echo "</span>";
echo "</div>";

//attributes are listed from JS
//prefer initial listing here for SEO


echo "</div>";


echo "</div>";


?>

<script type='text/javascript'>
    var piID = <?php echo $piID;?>;

    var attributes = <?php echo json_encode($attributes);?>

        onPageLoad(function () {

//   var first_color = $(".color_chooser .color_button").first();
//   changeColor(first_color.attr("pclrID"));

            var firstColorButton = $(".color_chooser .color_button[piID='" + piID + "']");
            if (firstColorButton) {
                var pclrID = firstColorButton.attr("pclrID");
                changeColor(pclrID);
                //   console.log(piID+"=>"+pclrID);
            }


        });

</script>

<?php
$page->finishRender();
?>
