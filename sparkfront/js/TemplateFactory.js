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

        //scripts are added to DOM
        return template.content.firstChild;
    }

    /**
     * Create node to insert in the document from html_text
     * @param html_text
     * @returns {ChildNode}
     */
    createElement(html_text) {

        this.factory.innerHTML = html_text;

        const element = this.factory.content.firstChild;

        //scripts are not added to DOM
        //Note: When inserted using the Document.write() method,
        // <script> elements execute (typically synchronously),
        // but when inserted using Element.innerHTML or Element.outerHTML, they do not execute at all.
        //copy scripts
        element.querySelectorAll("script").forEach((loading_script)=>{
            const script = document.createElement("script");
            script.text = loading_script.text;
            element.appendChild(script);
        });

        return element;
    }

}

document.templateFactory = new TemplateFactory();
