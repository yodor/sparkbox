function isMob() {
    if (navigator.userAgent.match(/Android/i)
        || navigator.userAgent.match(/webOS/i)
        || navigator.userAgent.match(/iPhone/i)
        || navigator.userAgent.match(/iPad/i)
        || navigator.userAgent.match(/iPod/i)
        || navigator.userAgent.match(/BlackBerry/i)
        || navigator.userAgent.match(/Windows Phone/i)
    ) {
        return true;
    } else {
        return false;
    }
}

function MenuBarComponent() {
    this.component_class = ".MenuBarComponent";
    this.cls = this.component_class + "[submenu_popup!=0]";
}

MenuBarComponent.prototype.toggleMenu = function (event, toggle) {


    var bar = toggle.parents(".MenuBar").first();


    if (bar) {
        console.log("Menu toggle clicked: " + bar.attr("name"));
        if (bar.hasClass("normal")) {
            bar.removeClass("normal");
            this.leaveAll();
        } else {
            bar.addClass("normal");
        }
    }


}
MenuBarComponent.prototype.attachWith = function (name) {

    this.name = name;


    var name_attribute = "[name='" + name + "']";
    this.cls += name_attribute;

    //find parent MenuBar find child toggle
    //attach onClick with instnce toggleMenu attach even with submenu_popup=0
    var toggle = $(this.component_class + name_attribute).parents(".MenuBar").first().children(".toggle").first();
    toggle.on("click", function (event) {
        //show/hide the MenuBarComponent
        instance.toggleMenu(event, toggle);
    });


    var instance = this;

    //link instance with dom
    $(this.cls).data("menu_instance", instance);


    //assign each menubar item with events
    $(this.cls).first().children(".MenuBarItemRenderer").each(function (index) {

        var barItem = $(this);
        var handle = barItem.find(".handle").first();


        if (isMob()) {
            if (barItem.attr("have_submenu")) {

                handle.attr("data-line", "▼");

                handle.on("click touch", function (event) {

                    if (barItem.hasClass("Hover")) {


                        instance.menuLeave(barItem);

                    } else {

                        instance.menuEnter(barItem);
                    }

                });//handle on click
            }
        } else {

            if (barItem.attr("have_submenu")) {

                handle.attr("data-line", "▼");

            }

            barItem.on("mouseenter", function (event) {

                instance.menuEnter(this);

            });
            barItem.on("mouseleave", function (event) {

                instance.menuLeave(this);

            });
            barItem.on("click", function (event) {
                var link_addr = barItem.children(".MenuItemOuter").first().children(".MenuItemLink").first();
                var href = link_addr.attr("href");
                window.location.href = href;
            });

        }

    });


    $(this.cls).first().find(".SubmenuItemRenderer").each(function (index) {

        var subItem = $(this);
        //only submenuitems containing submenus have data-line attribute defined
        var handle = subItem.find(".SubmenuItemOuter .handle[data-line]").first();

        handle.attr("data-line", "▼");

        //mouseenter is fired on click and touch too

        if (isMob()) {
            handle.on("click touch", function (event) {

                console.log($(subItem).hasClass("Hover"));

                if (subItem.hasClass("Hover")) {

                    console.log("Handle Clicked of Opened Submenu");
                    //this would hide all other submenus and this
                    instance.submenuLeave(subItem, event);

                } else {

                    console.log("Handle Clicked of Closed Submenu");
                    //this would hide all other menus but this
                    instance.submenuEnter(subItem, event);

                }

            });

        } else {
            subItem.on("mouseenter", function (event) {

                instance.submenuEnter(this, event);
                return false;
            });
        }


    });


}
MenuBarComponent.prototype.leaveAll = function () {
    var instance = this;

    $(this.cls).find(".MenuBarItemRenderer").each(function (index) {
        var mitem = $(this);
        instance.menuLeave(mitem);
    });


}
MenuBarComponent.prototype.setItemSelected = function (elm, mode) {

    if (!elm) return;

    var instance = this;

    $(elm).removeClass("Hover");
    var opened = "▲";
    var closed = "▼";
    var data_line = closed;

    if (mode) {

        $(elm).addClass("Hover");
        data_line = opened;
    }


    if ($(elm).hasClass("MenuBarItemRenderer")) {
        //search children only (first level) 
        var handle = $(elm).find(".handle").first();
        handle.attr("data-line", data_line);


    } else if ($(elm).hasClass("SubmenuItemRenderer")) {

        var handle = $(elm).children(".SubmenuItemOuter").first().children(".handle").first();
        handle.attr("data-line", data_line);
        //close all submenu items contained in this submenuitem
        if (!mode) {
            $(elm).find(".SubmenuItemRenderer.Hover").each(function (index) {
                instance.setItemSelected(this, false);
            });
        }


    }

}


MenuBarComponent.prototype.menuEnter = function (mitem) {
//     var source = $(mitem).parents("[source]").last().attr("source");
// 
    this.setItemSelected(mitem, true);

    var have_submenu = $(mitem).attr("have_submenu");

    if (have_submenu) {

        var pos = $(mitem).offset();
        var y = pos.top + $(mitem).outerHeight(true);

        this.submenuShow(mitem, pos.left, y);

    }


}

MenuBarComponent.prototype.menuLeave = function (mitem) {

    this.setItemSelected(mitem, false);

    var instance = this;

    $(mitem).find(".SubmenuRenderer").each(
        function (index) {
            $(this).removeClass("normal");
            $(this).find(".SubmenuItemRenderer.Hover").each(
                function (index1) {

                    instance.submenuLeave(this, event);


                }
            );
        }//index
    );//each

    $(mitem).find(".handle").first().data("opened", false);
}


MenuBarComponent.prototype.submenuEnter = function (smitem, event) {

    var pos = $(smitem).offset();
    var h = $(smitem).outerHeight(true);
    var v = $(smitem).outerWidth(true);

    var instance = this;

    instance.submenuLeave(smitem, event);

    instance.submenuShow(smitem, (pos.left + v), pos.top);

    instance.setItemSelected(smitem, true);


}

MenuBarComponent.prototype.submenuLeave = function (smitem, event) {

    var instance = this;

    //leaveAll including current
    $(smitem).parent(".SubmenuRenderer").find(".SubmenuItemRenderer").each(
        function (index) {

            instance.setItemSelected(this, false);

            $(this).find(".SubmenuRenderer").each(
                function (index1) {
                    $(this).removeClass("normal");
                }
            );
        }
    );

    instance.setItemSelected(smitem, false);


}


MenuBarComponent.prototype.submenuShow = function (mitem, left, top) {


//     console.log("Parent: " + mitem + " | left: " + left + " | top: " + top);

    var layout_direction = $(mitem).attr("submenu_direction");

    var submenu = $(mitem).children(".SubmenuRenderer").first();

//     submenu.css("min-width", menu_item.outerWidth(true));

    var margin_top = parseInt(submenu.css("margin-top"));
    if (!isNaN(margin_top) && margin_top > 0) {
        top += margin_top;
    }

    //make it visible
    submenu.addClass("normal");


    if (layout_direction == "right") {

// 	  left+=menu_item.outerWidth();
        left -= submenu.outerWidth();
        left += parseInt(menu_item.css("padding-left"));
        left += parseInt(menu_item.css("width"));
    } else {
//         console.log("To bottom. Left: " + left + " | Top: " + top);

    }

    submenu.offset({top: top, left: left});


}
