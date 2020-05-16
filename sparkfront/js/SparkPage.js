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

            } else {
                modal_pane.centerContents();
            }
            break;
    }
}

let popup_handler = new PopupHandler();
$(document).on("ModalPopup", popup_handler.popupEventHandler);

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
        var message_text = modal_pane.popup().find(".Inner .Contents");
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