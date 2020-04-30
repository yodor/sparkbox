(function ($) {
    $.event.special.destroyed = {
        remove: function (o) {
            if (o.handler) {
                o.handler()
            }
        }
    }
})(jQuery)

function PopupHandler() {

}

PopupHandler.prototype.popupEventHandler = function (e) {
    switch (e.message) {
        case "onPopupVisible":
            let modal_pane = e.pane;
            if (e.pane.fullscreen) {

            }
            else {
                modal_pane.centerContents();
            }
            break;
    }
}

let popup_handler = new PopupHandler();
$(document).on("ModalPopup", popup_handler.popupEventHandler);


function ModalPopup() {
    this.modal_content = "<div class='ModalPane' frameBorder=0 border=0 marginWidth=0 marginHeight=0 scrolling='no'></div>";
    this.fullscreen = false;
}

ModalPopup.prototype.popup = function () {
    return this.pane().children().first();
}
ModalPopup.prototype.pane = function () {
    return $(".ModalPane").last();
}

ModalPopup.prototype.showContent = function (content_html) {

    $("body").append(this.modal_content);

    let elm = $(content_html);
    elm.css("visibility", "hidden");

    this.pane().append(elm.get(0).outerHTML);

    this.show();

}

ModalPopup.prototype.showID = function (id) {
    if ($("#" + id).length < 1) {
        showAlert("Requested element #" + id + " was not found");
        return;
    }

    $("body").append(this.modal_content);

    let elm = $("#" + id);
    elm.data("originate_parent", elm.parent());

    this.pane().append(elm);
    elm.css("visibility", "hidden");
    this.show();

    elm.bind('destroyed', function () {
        let originate_parent = elm.data("originate_parent");
        originate_parent.append(elm);
        elm.css("visibility", "hidden");
    })

    //TODO:check if the elment need to go back to the dom after closing the modal pane
}

ModalPopup.prototype.show = function () {



    if (this.fullscreen) {
    }
    else {
        this.popup().css("display", "block");
    }

    this.popup().css("visibility", "visible");

    $.event.trigger({
        type: "ModalPopup",
        message: "onPopupVisible",
        time: new Date(),
        pane: this,
        popup: this.popup()
    });

    if (this.fullscreen) {

    }
    else {
        $(window).resize(function (event) {
            this.centerContents();
        }.bind(this));
    }

    this.pane().click(function (event) {
        this.paneClicked(event);
    }.bind(this));


    let caption = this.popup().find(".caption");
    if (caption.get(0)) {
        caption.mousedown(function (event) {
            if (event.which != 1) return;

            let pos = this.popup().offset();
            let deltaX = event.pageX - pos.left;
            let deltaY = event.pageY - pos.top;

            $(window).mousemove(function (event1) {

                if (event1.which != 1) return;

                this.popup().css("left", event1.pageX - deltaX);
                this.popup().css("top", event1.pageY - deltaY);

            }.bind(this));

        }.bind(this));

        caption.mouseup(function (event) {
            $(window).unbind("mousemove");
        });
    }

    let last_pane = $("body").find(".ModalPane").last();
    if (last_pane.get(0)) {
        last_pane.data("control_object", this);
    }
}
ModalPopup.prototype.paneClicked = function (event) {
//   console.log("paneClicked");

}
ModalPopup.prototype.centerContents = function () {

    var windowWidth = $(window).width(); //retrieve current window width
    var windowHeight = $(window).height(); //retrieve current window height

    var width = this.popup().outerWidth(true);
    var height = this.popup().outerHeight(true);

    var left = ((windowWidth - width) / 2);
    var top = ((windowHeight - height) / 2);

    this.popup().css("left", left);
    this.popup().css("top", top);


}

function showAlert(text, func) {

    let dialog = new MessageDialog();
    dialog.setText(text);
    dialog.setCaption("Alert!");

    dialog.show();

    dialog.modal_pane.popup().find("[action='confirm']").click(function (event) {
        if (func) {
            console.log("Calling func");
            func(dialog)
        } else {
            console.log("Calling remove");
            dialog.modal_pane.pane().remove();
        }
    });

    dialog.modal_pane.paneClicked = function (event) {

    }

}

function showConfirm(text, func_ok, func_cancel) {


    var dialog = new MessageDialog("confirm_dialog");
    dialog.setText(text);
    dialog.setCaption("Confirmation");
    dialog.show();

    dialog.modal_pane.popup().find("[action='confirm']").click(function (event) {
        if (func_ok) {
            console.log("Calling func OK");
            func_ok(dialog)
        } else {
            console.log("Calling default remove");
            dialog.modal_pane.pane().remove();
        }
    });

    dialog.modal_pane.popup().find("[action='cancel']").click(function (event) {
        if (func_cancel) {
            console.log("Calling func CANCEL");
            func_cancel(dialog)
        } else {
            console.log("Calling default remove");
            dialog.modal_pane.pane().remove();
        }
    });

    dialog.modal_pane.paneClicked = function (event) {

    }

}

function showPopupPanel(popup_id, text) {
    var modal_pane = new ModalPopup();

    modal_pane.showID(popup_id);

    if (text) {
        var message_text = modal_pane.popup().find(".message_text");
        message_text.html(text);
    }

    modal_pane.centerContents();

    modal_pane.paneClicked = function (event) {

    }

}


$(document).bind("keypress", function (event) {


    //console.log("Keypress: "+event.which);

    var pane = $(".ModalPane").last();

    if (!pane.get(0)) {
        return true;
    }

    var control_object = pane.data("control_object");

    //esc
    if (event.which == 27) {


        var cancel_button = control_object.popup().find("[action='cancel']").first();
        var confirm_button = control_object.popup().find("[action='confirm']").first();

        if (cancel_button.get(0)) {
            cancel_button.click();
            return false;
        } else if (confirm_button.get(0)) {
            confirm_button.click();
            return false;
        } else {
            pane.trigger("click");
            return false;
        }

    } else if (event.which == 13) {


        var defaul_button = control_object.popup().find("[default_action]").first();
        if (defaul_button.get(0)) {

            defaul_button.click();
            return false;
        }

    }

    return true;


});
