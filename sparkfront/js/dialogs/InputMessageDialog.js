class InputMessageDialog extends MessageDialog {

    constructor() {
        super();
        this.input = this.element.querySelector("INPUT");
    }

    show() {
        super.show();
        this.input.focus();
    }


}