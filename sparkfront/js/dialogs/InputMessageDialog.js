class InputMessageDialog extends MessageDialog {

    constructor() {

        super();
        this.setID("input_dialog");

    }

    show() {
        super.show();
        this.input().focus();
    }

    input() {
        return $(this.visibleSelector()).find("[name='user_input']").first();
    }

}