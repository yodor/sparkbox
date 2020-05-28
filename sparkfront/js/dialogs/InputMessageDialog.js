class InputMessageDialog extends MessageDialog {

    constructor() {

        super();
        this.setID("input_dialog");

    }

    show() {
        super.show();
        this.input().focus();
    }

    /**
     * The input field of this dialog
     * @returns {jQuery}
     */
    input() {
        return $(this.visibleSelector() + " INPUT").first();
    }

}