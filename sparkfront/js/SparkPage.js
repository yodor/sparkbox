/**
 * Show the default message dialog with text 'text'
 * If onConfirm function is passed call it when confirm action is triggered
 * @param text {string}
 * @param buttonAction {function(action)}
 */
function showAlert(text) {

    return MessageDialog.ShowAlert(text);

}

