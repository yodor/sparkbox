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
     * @returns {Node[]}
     */
    nodeList(html_text) {

        this.factory.innerHTML = html_text;

        const collection = this.factory.content.children;

        const nodeList = Array.from(collection);

        // console.log(collection);
        //scripts are not added to DOM
        //Note: When inserted using the Document.write() method,
        // <script> elements execute (typically synchronously),
        // but when inserted using Element.innerHTML or Element.outerHTML, they do not execute at all.
        //copy scripts
        this.factory.content.querySelectorAll("script").forEach((loading_script)=>{
            const script = document.createElement("script");
            script.text = loading_script.text;
            nodeList.push(script);
        });

        return nodeList;
    }

    appendNodes(element, nodeList)
    {
        nodeList.forEach((node) => {
            element.appendChild(node);
        });

        const event = new SparkEvent(SparkEvent.DOM_UPDATED);
        event.source = element;
        document.dispatchEvent(event);
    }

    appendContent(element, html_text)
    {
        const nodeList = this.nodeList(html_text);
        this.appendNodes(element, nodeList);
        return nodeList;
    }
}

document.templateFactory = new TemplateFactory();
