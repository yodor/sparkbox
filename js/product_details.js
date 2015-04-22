
function changeColor(pclrID) 
{
  //unselect all items
  $(".image_gallery .list .item").attr("active", "0");
  $(".color_chooser .color_button").attr("active", "0");
  
  
  var color_button = $(".color_chooser .color_button[pclrID='"+pclrID+"']");
  color_button.attr("active", "1");
  
  //available pids from this color
  piID = color_button.attr("piID");
  
  var size_values = color_button.attr("size_values");
  var sizes = size_values.split("|");
//   console.log(sizes);
  
  var size_chooser = $(".size_chooser .product_size");
  size_chooser.empty();
  for (var a=0;a<sizes.length;a++) {
	size_chooser.append("<option>"+sizes[a]+"</option>");
  }

  //hide other galleries
  $(".image_gallery .list").css("display", "none");
  var active_gallery = $(".image_gallery .list[pclrID='"+pclrID+"']");
  active_gallery.css("display", "block");
  
  var first_item = active_gallery.find(".item").first(); //$(".image_gallery .list[pclrID='"+pclrID+"'] .item").first();

  changeImage(first_item);
  updatePrice();
  
}

//update the main image
function changeImage(elm)
{
  //deselect all gallery items
  $(".image_gallery .list .item").attr("active", "0");
  
  var bean = $(elm).attr("bean");
  var id = $(elm).attr("itemID");
  var href_big = $(".image_big").attr("source");
  href_big += "&class="+bean+"&id="+id;
  
  $(".image_big IMG").attr("src", href_big);

  $(elm).attr("active", "1");
  
  var href_popup = $(".image_big A").attr("source");
  $(".image_big A").attr("href", href_popup+"&class="+bean+"&id="+id);
  
}

function updatePrice()
{
  console.log("Update Price");
  
  var color_chooser = $(".color_chooser .color_button[active='1']");
  var prices = color_chooser.attr("sell_prices");
  var sell_prices = prices.split("|");
  var pid_values = color_chooser.attr("pids");
  var pids = pid_values.split("|");
  
  console.log("Prices: " + sell_prices);
  console.log("piIDs: " + pids);
  
  var size_chooser = $(".size_chooser .product_size");
  var index = size_chooser.prop("selectedIndex");
  
  console.log("selected index: " + index);
  console.log("selected price: " + sell_prices[index]);
  $(".price_panel .sell_price").html(parseFloat(sell_prices[index]).toFixed(2));
  
  var pid = pids[index];
  console.log("selected piD: " + pid);
  
  $(".price_panel .sell_price").attr("pid", pid);
  
  
}