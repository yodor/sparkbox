class BeanTranslationDialog extends JSONDialog {

    constructor() {

        super();

        this.language_alert = "Please select translation language";

        this.languageInput = this.element.querySelector("[name='langID']")
        this.languageInput.value = "";

        this.languageInput.addEventListener("change", (event)=>this.changeLanguage());

        this.originalInput = this.element.querySelector("[name='original_text']");
        this.translationInput = this.element.querySelector("[name='translation']");

    }

    buttonAction(action) {
        if (action === "Translate") {
            this.store();
        } else if (action === "Clear") {
            this.clear();
        } else if (action === "Close") {
            this.remove();
        }
    }

    changeLanguage() {

        this.langID = parseInt(this.languageInput.value);

        this.translationInput.value = "";

        this.request.setParameter("langID", ""+this.langID);

        this.fetch();
    }

    show(field_name, is_mce= false) {

        this.is_mce = is_mce;

        super.show();

        const editor = document.querySelector(".BeanFormEditor");

        //bean_id, field_name, bean_class
        let bean_id = editor.getAttribute("editID");
        let bean_class = editor.getAttribute("bean");

        console.log("beanID: " + bean_id + " bean_class: " + bean_class + " DataInput: " + field_name);

        this.request.setParameter("field_name", field_name);
        this.request.setParameter("beanID", bean_id);
        this.request.setParameter("bean_class", bean_class);

        this.request.setParameter("langID", this.languageInput.value);

        this.originalInput.value = "";
        this.originalInput.value = editor.querySelector("[name='"+field_name+"']").value;
        this.fetch();
    }

    processResult(responder, funct, result) {
        let response = result.response;
        let message = response.message;

        if (funct === "store") {

            showAlert(message);

        } else if (funct === "fetch") {

            if (!response.translation) showAlert(message);
            this.translationInput.value = response.translation;

        } else if (funct === "clear") {

            this.translationInput.value = "";
            showAlert(message);
        }
    }

    ensureLangID() {
        if (this.langID < 1) {
            throw this.language_alert;
        }
    }
    store() {

        this.ensureLangID();

        this.request.setFunction("store");
        this.request.setPostParameter("translation", this.translationInput.value);
        this.request.start();
    }


    fetch() {

        this.ensureLangID();

        this.request.setFunction("fetch");
        this.request.clearPostParameters();
        this.request.start();

    }

    clear() {

        this.ensureLangID();

        this.request.setFunction("clear");
        this.request.clearPostParameters();
        this.request.start();
    }


}