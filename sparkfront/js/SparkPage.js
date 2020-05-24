(function ($) {
    $.event.special.destroyed = {
        remove: function (o) {
            if (o.handler) {
                o.handler()
            }
        }
    }
})(jQuery)

// class PopupHandler {
//     handleEvent(e) {
//         switch (e.message) {
//             case "onPopupVisible":
//                 let modal_pane = e.pane;
//                 if (e.pane.fullscreen) {
//
//                 } else {
//                     modal_pane.centerContents();
//                 }
//                 break;
//         }
//     }
// }


// let popup_handler = new PopupHandler();
// $(document).on("ModalPane", popup_handler.handleEvent);

/**
 * Show the default message dialog with text 'text'
 * If onConfirm function is passed call it when confirm action is triggered
 * @param text {string}
 * @param buttonAction {function(action)}
 */
function showAlert(text) {

    let dialog = new MessageDialog();
    dialog.setText(text);
    dialog.setCaption("Alert!");
    dialog.initialize();

    dialog.show();

    dialog.buttonAction = function(action) {
        dialog.remove();
    };
    return dialog;

}


