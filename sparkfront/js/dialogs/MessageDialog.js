class MessageDialog extends TemplateComponent {

    static TYPE_PLAIN = "Plain";
    static TYPE_ERROR = "Error";
    static TYPE_INFO = "Info";
    static TYPE_QUESTION = "Question";


    static indexCounter = -1;

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

        //increment the global index counter
        MessageDialog.indexCounter++;
        //set this selector index
        this.index = MessageDialog.indexCounter;

        this.element.setAttribute("index", this.index);

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
        this.shown = false;
    }

    /**
     * Default handler remove the dialog
     * @param action {string} Button action attribute value
     */
    buttonAction(action) {
        //console.log("buttonAction() : " + action);
        this.remove();
    }

    static ShowAlert(text)
    {
        let dialog = new MessageDialog();
        dialog.setText(text);
        dialog.setTitle("Alert!");

        dialog.buttonAction = function(action) {
            dialog.remove();
        };

        dialog.show();

        return dialog;
    }

}
