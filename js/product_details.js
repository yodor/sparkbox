function changeColor(pclrID) {
    console.log("Changing color scheme to: " + pclrID);

    //unselect all items
    $(".image_gallery .list .item").attr("active", "0");
    $(".color_chooser .value .color_button").attr("active", "0");

    var available_colors = $(".color_chooser .value .color_button").length;
    console.log("Available colors: " + available_colors);

    var color_button = $(".color_chooser .value .color_button[pclrID='" + pclrID + "']");
    color_button.attr("active", "1");

    var color_name = color_button.attr("color_name");
    $(".current_color .value").html(color_name);

    //hide single color schemes
    if (available_colors == 1 && !color_name) {
        $(".item.current_color").css("display", "none");

    }

    //available pids from this color
    piID = color_button.attr("piID");

    var size_values = color_button.attr("size_values");
    var sizes = size_values ? size_values.split("|") : Array();

    //   console.log(sizes);
    //   console.log(sizes.length);

    var size_chooser = $(".product_details .item.size_chooser .value .product_size");
    //
    size_chooser.parents(".item.size_chooser").first().css("display", "none");
    size_chooser.empty();

    for (var a = 0; a < sizes.length; a++) {
        size_chooser.append("<option>" + sizes[a] + "</option>");
    }

    if (sizes.length > 0) {
        size_chooser.parents(".item.size_chooser").first().css("display", "");
    }


    //hide other galleries
    $(".image_gallery .list").css("display", "none");
    var active_gallery = $(".image_gallery .list[pclrID='" + pclrID + "']");
    active_gallery.css("display", "block");

    var first_item = active_gallery.find(".item").first(); //$(".image_gallery .list[pclrID='"+pclrID+"'] .item").first();

    changeImage(first_item);
    updatePrice();

}

//update the main image
function changeImage(elm) {
    //deselect all gallery items
    $(".image_gallery .list .item").attr("active", "0");

    var bean = $(elm).attr("bean");
    var id = $(elm).attr("itemID");
    var href_big = $(".image_big").attr("source");
    href_big += "&class=" + bean + "&id=" + id;

    $(".image_big IMG").attr("src", href_big);

    $(elm).attr("active", "1");

    var href_popup = $(".image_big A").attr("source");
    $(".image_big A").attr("href", href_popup + "&class=" + bean + "&id=" + id);

}

function updatePrice() {
    console.log("Update Price");

    var color_chooser = $(".color_chooser .value .color_button[active='1']");
    var prices = color_chooser.attr("sell_prices");
    var sell_prices = prices.split("|");
    var pid_values = color_chooser.attr("pids");
    var pids = pid_values.split("|");

    console.log("Prices: " + sell_prices);
    console.log("piIDs: " + pids);

    var size_chooser = $(".size_chooser .value");
    var index = size_chooser.prop("selectedIndex");

    console.log("selected index: " + index);
    if (index > -1) {
        console.log("selected price: " + sell_prices[index]);
        $(".sell_price .value").html(parseFloat(sell_prices[index]).toFixed(2));

        var pid = pids[index];
        console.log("selected piD: " + pid);

        $(".sell_price .value").attr("pid", pid);
    } else {

        var pid = parseInt($(".sell_price .value").attr("piID"));
        $(".sell_price .value").html(parseFloat(sell_prices).toFixed(2));
    }

    var attrib = attributes[pid];
    var html = "";
    for (var idx = 0; idx < attrib.length; idx++) {
        var obj = attrib[idx];
        //sellable inventory has class attribute value filled in
        if (obj.value) {
            html += "<div class='item attributes'>";
            html += "<label>" + obj.name + "</label>";
            html += "<span class='value'>" + obj.value + "</span>";
            html += "</div>";
        }
    }
    $(".product_details .item.attributes").remove();
    $(".product_details").append(html);


}
