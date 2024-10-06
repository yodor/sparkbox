class TemplateFactory {

    constructor() {
        this.factory = document.createElement('template');
        this.templateList = null;
    }

    initialize() {

        this.templateList = document.querySelectorAll("TEMPLATE");
        this.templateList?.forEach((element) => {
            //const id = element.getAttribute("id");
        })

    }

    /**
     * Get ChildNode of template #id
     * @param id
     * @returns {*}
     */
    getElement(id) {
        const template = this.templateList.querySelector("#"+id);
        return template.content.firstChild;
    }
    /**
     * Create node to insert in the document from html_text
     * @param html_text
     * @returns {ChildNode}
     */
    createElement(html_text) {
        this.factory.innerHTML = html_text;
        return this.factory.content.firstChild;
    }

}

document.templateFactory = new TemplateFactory();
onPageLoad(()=>{
    document.templateFactory.initialize();
});
