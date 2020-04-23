function clearFilters() {
    var uri = new URI(document.location.href);
//   console.log(uri.filename());
    document.location.href = uri.filename();
}

function filterChanged(elm, filter_name, is_combined) {
    var elm = $(elm);

    var name = (filter_name) ? filter_name : elm.attr("name");

    var value = elm.val();


    if (is_combined) {
        //value = elm.attr("name")+":"+value;
        //prepare GET query string Материал:пух|Години:12
        var values = [];
        $("[filter_group='" + filter_name + "']").each(function (idx) {
            var val = $(this).val();
            if (val) {
                values.push($(this).attr("name") + ":" + val);
            }

        });
        value = values.join("|");
    }
    console.log("Filter changed: " + name + " => " + value);


    var uri = new URI(document.location.href);
    uri.removeSearch(name);
    uri.addSearch(name, value);

    var href = uri.href();
    console.log(href);

    document.location.href = href;

}

onPageLoad(function () {

    $(".drag").slider({
        range: true,
        min: 0,
        max: 100,
        values: [0, 100],
        slide: function (event, ui) {
            var min = parseFloat(ui.values[0]).toFixed(2);
            var max = parseFloat(ui.values[1]).toFixed(2);
            $(this).parents(".Slider").children(".value").html(min + " - " + max);
            $(this).parent().children("[name='price_range']").attr("value", min + "|" + max);
        },
        stop: function (event, ui) {
            var min = parseFloat(ui.values[0]).toFixed(2);
            var max = parseFloat(ui.values[1]).toFixed(2);
            $(this).val(min + "|" + max);
            filterChanged(this, "price_range");
        }
    });

    var min = Number.parseFloat($(".drag").attr("min"));
    var max = Number.parseFloat($(".drag").attr("max"));

    var value_min = min;
    var value_max = max;

    var price_range = $(".drag").parent().children("[name='price_range']").attr("value");
    var price_range = price_range.split("|");

    if (price_range.length == 2) {
        value_min = Number.parseFloat(price_range[0]);
        value_max = Number.parseFloat(price_range[1]);
    }
//     console.log("value-min: "+value_min);
//     console.log("value-min: "+value_max);

    $(".drag").slider("option", "min", min);
    $(".drag").slider("option", "max", max);

    $(".drag").slider("option", "values", [value_min, value_max]);

    $(".drag").parents(".Slider").children(".value").html(value_min + " - " + value_max);

});
