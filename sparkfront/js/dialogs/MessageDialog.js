class MessageDialog extends TemplateComponent {

    static TYPE_PLAIN = "Plain";
    static TYPE_ERROR = "Error";
    static TYPE_INFO = "Info";
    static TYPE_QUESTION = "Question";


    /**
     * The index of the last dialog shown - each dialog its own (the selector of a dialog has it)
     */
    static indexCounter = -1;

    /**
     * The dialogs shown, the top one last - Escape answers the top one, the z-index follows the order
     * @type {MessageDialog[]}
     */
    static openDialogs = [];

    /**
     * Escape: the 'cancel' action of the top dialog when it has one, else its default action
     * @param event {KeyboardEvent}
     */
    static onKeyDown(event) {
        if (event.key !== "Escape" || MessageDialog.openDialogs.length < 1) return;
        const dialog = MessageDialog.openDialogs[MessageDialog.openDialogs.length - 1];
        const buttons = dialog.element.querySelector(".Footer .Buttons");
        let action = "";
        if (buttons?.querySelector("[action='cancel']")) action = "cancel";
        else action = buttons?.querySelector("[default_action]")?.getAttribute("action") || "";
        event.preventDefault();
        if (action) dialog.buttonAction(action);
        else dialog.remove();
    }

    /**
     * Create a DOM element selector for MessageDialog popups
     */
    constructor(templateID=null) {
        super(templateID);

        this.index = -1;

        this.type = ""+this.element.getAttribute("type");

        this.modal = this.element.hasAttribute("modal");
        this.single = this.element.hasAttribute("single");

        this.header = this.element.querySelector(".Inner .Header");
        this.content = this.element.querySelector(".Inner .Content");
        this.footer = this.element.querySelector(".Inner .Footer");

        this.buttons = this.element.querySelector(".Inner .Footer .Buttons");

        this.shown = false;
    }

    initialize() {
        super.initialize();
    }

    selector() {
        let result = super.selector();
        result += "[index='" + this.index + "']";
        return result;
    }

    setTitle(html) {
        this.header.querySelector(".Title").innerHTML = html;
    }

    setText(html) {
        this.content.querySelector(".Text").innerHTML = html;
    }

    setContent(html) {
        this.content.innerHTML = html;
    }

    setType(type) {
        this.element.setAttribute("type", type);
    }

    setModal(mode) {
        if (mode) {
            this.element.setAttribute("modal","");
        }
        else {
            this.element.removeAttribute("modal");
        }
    }


    show() {
        if (this.shown) return;

        this.shown = true;

        //an index of its own - never reused while the page is open (two dialogs with one index share a selector)
        MessageDialog.indexCounter++;
        this.index = MessageDialog.indexCounter;

        this.element.setAttribute("index", this.index);

        //a dialog for assistive technology, named by its title; above the dialogs shown before it
        this.element.setAttribute("role", "dialog");
        this.element.setAttribute("aria-modal", "true");
        const title = this.header?.querySelector(".Title")?.textContent?.trim();
        if (title) this.element.setAttribute("aria-label", title);
        MessageDialog.openDialogs.push(this);
        this.element.style.zIndex = String(100 + MessageDialog.openDialogs.length);
        //the focus goes back where it was when the dialog closes
        this.returnFocus = document.activeElement;

        const buttons = this.element.querySelector(".Footer .Buttons");

        buttons.querySelectorAll("[action]").forEach((element)=>{
            element.addEventListener("click", (event) => {
                const action = element.getAttribute("action");
                if (action) {
                    this.buttonAction(action);
                }
            });
        });

        buttons.querySelector("[default_action]")?.focus();

        this.render();
    }

    remove() {
        super.remove();
        MessageDialog.openDialogs = MessageDialog.openDialogs.filter((dialog) => dialog !== this);
        this.shown = false;
        if (this.returnFocus instanceof HTMLElement && document.contains(this.returnFocus)) this.returnFocus.focus();
        this.returnFocus = null;
    }

    /**
     * Default - remove the dialog
     * @param action {string} Button action attribute value
     */
    buttonAction(action) {
        //console.log("buttonAction() : " + action);
        this.remove();
    }

}

/**
 * Show the default message dialog with text 'text' and return instance to the dialog
 *
 * @param text {string}
 * @return {MessageDialog}
 */
function showAlert(text) {

    const dialog = new MessageDialog();
    dialog.setText(text);
    dialog.setTitle(tr("Message"));

    dialog.buttonAction = function(action) {
        dialog.remove();
    };

    dialog.show();
    return dialog;

}

document.addEventListener("keydown", (event) => MessageDialog.onKeyDown(event));