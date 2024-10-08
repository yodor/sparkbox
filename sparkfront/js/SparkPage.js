(function ($) {
    $.event.special.destroyed = {
        remove: function (o) {
            if (o.handler) {
                o.handler()
            }
        }
    }
})(jQuery)

/**
 * Show the default message dialog with text 'text'
 * If onConfirm function is passed call it when confirm action is triggered
 * @param text {string}
 * @param buttonAction {function(action)}
 */
function showAlert(text) {

    return MessageDialog.ShowAlert(text);

}

