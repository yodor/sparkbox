class InputMessageDialog extends MessageDialog {

    constructor() {
        super();

    }

    show() {
        super.show();
        this.input.focus();
    }

    initialize() {
        super.initialize();
        this.input = this.element.querySelector(".Input");
    }
}