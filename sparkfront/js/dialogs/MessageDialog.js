class MessageDialog extends Component {

    static indexCounter = -1;

    /**
     * Create a DOM element selector for MessageDialog popups
     */
    constructor() {
        super();
        this.setClass(".MessageDialog");
        this.setID("message_dialog");

        this.text = "";
        this.caption = "";

        this.modal_pane = new ModalPopup();

        this.icon_enabled = true;

        this.index = -1;

        //singleInstance
        this.single = false;

        this.singleInstance = null;
    }

    initialize() {
        super.initialize();
        if (this.component().attr("single")) {
            this.single = true;
        }
    }
    /**
     * Return the cloned jQuery object to show inside modal pane
     * @returns {jQuery}
     */
    createContent() {

        let cnt = this.component().clone(true, true);

        if (this.single) {
            //copy
            this.singleInstance = this.component().clone(true, true);
            this.component().remove();
        }

        if (this.caption) {
            cnt.find(".Caption .Title").html(this.caption);
        }

        if (this.text) {
            cnt.find(".Inner .Contents .Text").html(this.text);
        }

        if (!this.icon_enabled) {
            cnt.find(".Contents .Icon").remove();

        }

        return cnt;
    }

    setCaption(caption) {
        this.caption = caption;
        $(this.visibleSelector()).find(".Caption .Title").html(this.caption);
    }

    setText(text) {
        this.text = text;
        $(this.visibleSelector()).find(".Inner .Contents .Text").html(this.text);
    }

    visibleSelector() {
        let result = this.selector();
        result += "[index='" + this.index + "']";
        return result;
    }

    show() {

        //increment the global index counter
        MessageDialog.indexCounter++;
        //set this selector index
        this.index = MessageDialog.indexCounter;

        let element = this.createContent();

        element.attr("index", this.index);

        this.modal_pane.showContent(element);

        let buttonsBar = $(this.visibleSelector()).find(".Buttons");

        let instance = this;

        //setup button actions
        buttonsBar.find("[action]").each(function(index) {
           $(this).on("click", function (event) {
               let action = $(this).attr("action");
               instance.buttonAction(action);
           });
        });

        buttonsBar.find("[default_action]").first().focus();

        $(window).resize();
    }

    remove() {

        let pane = $(this.modal_pane.visualSelector());

        if (pane) {
            pane.remove();
        } else {
            console.log(this.constructor.name + this.selector() + " not shown yet");
        }

        if (this.singleInstance) {
            $("body").append(this.singleInstance);
            this.singleInstance = null;
        }
    }

    /**
     *
     * @param action {string} Button action attribute value
     */
    buttonAction(action) {
        console.log(this.visibleSelector() + "::buttonAction() - Default handler: " + action);
    }


}
