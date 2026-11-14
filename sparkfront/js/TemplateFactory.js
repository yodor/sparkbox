class TemplateFactory {

    constructor() {
        this.factory = document.createElement('template');
    }

    /**
     * Get firstChild of template #id
     * @param id {string}
     * @returns {Node}
     */
    getElement(id) {
        const template = document.querySelector( "template#" + id);
        if (!template) throw "template#"+id+ " not found";

        //scripts are added to DOM
        return template.content.firstChild;
    }

    /**
     * Create Node[] array from string containing HTML
     * Look for scripts in the string and create nodes for them in the nodeList
     * @param html_text HTML string to create nodes from
     * @returns {Node[]} Resulting Node[] array
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

    /**
     * Append each node from the 'nodeList' array to 'element' Node and dispatch SparkEvent.DOM_UPDATED
     * @param element {Node}
     * @param nodeList {Node[]}
     */
    appendNodes(element, nodeList)
    {
        nodeList.forEach((node) => {
            element.appendChild(node);
        });

        const event = new SparkEvent(SparkEvent.DOM_UPDATED);
        event.source = element;
        document.dispatchEvent(event);
    }

    /**
     * nodeList + appendNodes combined method
     * @param element {Node} DOM node to append the resulting nodes to
     * @param html_text {string} HTML string
     * @returns {Node[]} Resulting nodeList that was created from html_text
     */
    appendContent(element, html_text)
    {
        const nodeList = this.nodeList(html_text);
        this.appendNodes(element, nodeList);
        return nodeList;
    }
}

document.templateFactory = new TemplateFactory();
