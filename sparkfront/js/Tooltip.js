function showTooltip(str, event) {

    var existing_tooltip = $(".TooltipPanel").first();

    if (existing_tooltip.get(0)) {
        str = str + "<BR>" + existing_tooltip.html();
        existing_tooltip.html(str);
    } else {
        var tooltip_content = "<div class='TooltipPanel'>" + str + "</div>";
        $("body").append(tooltip_content);
    }

    var tooltip = $("body").children(".TooltipPanel").first();

    var left = event.pageX + 20;
    var top = event.pageY + 20;

    tooltip.css("left", left);
    tooltip.css("top", top);
    tooltip.css("display", "block");

}

function hideTooltip() {
    $(".TooltipPanel").remove();

}

function processTooltipContent(elm) {
    if (!elm) elm = "body";

    $(elm).find("[tooltip]").each(function (index) {
        var is_tooltip = $(this).data("tooltip_attached");
        if (is_tooltip) return;

        $(this).data("tooltip_attached", 1);

        var str = $(this).attr("tooltip");

        if (!str) return;

        var trig = $(this);

        if (trig.attr("error")) {
            str = "Error: " + str;
        }

        if (trig.hasClass("MCETextArea")) {
            var fields = trig.children().last();
            if (fields.get(0)) trig = $(fields);
        }

        trig.hover(
            function (event) {
                showTooltip(str, event);
            },
            function (event) {
                hideTooltip();
            }
        );

    });
}

onPageLoad(function () {
    processTooltipContent();
}, true);