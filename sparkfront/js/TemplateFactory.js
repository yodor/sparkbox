class TemplateFactory {

    constructor() {
        this.factory = document.createElement('template');
    }

    /**
     * Get ChildNode of template #id
     * @param id
     * @returns {Node}
     */
    getElement(id) {
        const template = document.querySelector( "template#" + id);
        if (!template) throw "template#"+id+ " not found";
        return template.content.firstChild;
    }

    /**
     * Create node to insert in the document from html_text
     * @param html_text
     * @returns {Node}
     */
    createElement(html_text) {
        this.factory.innerHTML = html_text;
        return this.factory.content.firstChild;
    }

}

document.templateFactory = new TemplateFactory();
