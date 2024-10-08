class BeanTranslationDialog extends JSONDialog {

    constructor() {

        super();

        this.language_alert = "Please select translation language";

        this.req.setResponder("bean_translator");

        this.languageInput = this.element.querySelector("[name='langID']")
        this.languageInput.value = "";

        this.languageInput.addEventListener("change", (event)=>this.changeLanguage());

        this.originalInput = this.element.querySelector("[name='original_text']");
        this.translationInput = this.element.querySelector("[name='translation']");

    }

    buttonAction(action) {
        if (action == "Translate") {
            this.store();
        } else if (action == "Clear") {
            this.clear();
        } else if (action == "Close") {
            this.remove();
        }
    }

    changeLanguage() {

        this.langID = parseInt(this.languageInput.value);

        this.translationInput.value = "";

        this.req.setParameter("langID", ""+this.langID);

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

        this.req.setParameter("field_name", field_name);
        this.req.setParameter("beanID", bean_id);
        this.req.setParameter("bean_class", bean_class);

        this.req.setParameter("langID", this.languageInput.value);

        this.originalInput.value = editor.querySelector(`INPUT[name='${field_name}']`).value;
        this.fetch();
    }

    processResult(responder, funct, result) {
        let jsonResult = result.json_result;
        let message = jsonResult.message;

        if (funct == "store") {

            showAlert(message);

        } else if (funct == "fetch") {

            if (!jsonResult.translation) showAlert(message);
            this.translationInput.value = jsonResult.translation;

        } else if (funct == "clear") {

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

        this.req.setFunction("store");
        this.req.setPostParameter("translation", this.translationInput.value);
        this.req.start();
    }


    fetch() {

        this.ensureLangID();

        this.req.setFunction("fetch");
        this.req.clearPostParameters();
        this.req.start();

    }

    clear() {

        this.ensureLangID();

        this.req.setFunction("clear");
        this.req.clearPostParameters();
        this.req.start();
    }


}

