class ModalPopup {
    static indexCounter = -1;

    constructor() {

        this.className = ".ModalPane";


        this.modal_content = "<div class='ModalPane'></div>";


        const template = document.createElement('template');
        template.innerHTML = this.modal_content;
        this.modalElement = template.content.firstChild;


        this.fullscreen = false;

        this.index = -1;

        /**
         *
         * @type {jQuery}
         */
        this.popup = null;

        /**
         *
         * @type {jQuery}
         */
        this.pane = null;

    }

    /**
     * JQuery selector
     * @param html_element
     */
    showContent(html_element) {

        ModalPopup.indexCounter++;

        this.index = ModalPopup.indexCounter;

        html_element.css("visibility", "hidden");

        let pane = $(this.modal_content);
        pane.attr("index", this.index);

        pane.append(html_element);


        $("body").append(pane);

        this.pane = $(this.visualSelector());
        this.popup = this.pane.children().first();

        this.show();

    }

    visualSelector() {
        return this.className + "[index='" + this.index + "']";
    }

    show() {

        this.pane.data("control_object", this);

        this.pane.on("click", function (event) {
            this.paneClicked();
        }.bind(this));


        if (!this.fullscreen) {
            this.popup.css("display", "block");
        }

        this.makeResizable();

        this.makeMovable();

        this.popup.css("visibility", "visible");

        $.event.trigger({
            type: "ModalPane",
            message: "contentShown",
            time: new Date(),
            pane: this,
        });

    }

    close() {
        if (this.pane) {
            this.pane.remove();
        }
    }

    paneClicked(event) {

    }

    centerContents() {

    }

    makeMovable() {

    }

    /*Make resizable div by Hung Nguyen*/
    makeResizable() {

    }


}


$(document).on("keyup", function (event) {

    let pane = $(".ModalPane[index]").last();
    if (!pane.get(0)) return true;

    let cancel_button = pane.find("[action='cancel']").first();
    let confirm_button = pane.find("[action='confirm']").first();
    let default_button = pane.find("[action][default_action]").first();

    if (event.which == 27) {

        if (cancel_button.get(0)) {
            cancel_button.click();
        } else if (confirm_button.get(0)) {
            confirm_button.click();
        } else {
            pane.click();
        }
        return false;
    } else if (event.which == 13) {

        if (confirm_button.get(0)) {
            confirm_button.click();
        } else if (default_button.get(0)) {
            default_button.click();
        } else {
            return true;
        }

        return false;
    }

    return false;
});
