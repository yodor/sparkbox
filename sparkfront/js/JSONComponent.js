class JSONComponent extends Component {
    static EVENT_ELEMENT_CREATED = "element_created";

    /**
     * Create JSONComponent calling backend responder name '$name'
     * @param name
     */
    constructor(responder_name) {
        super();

        //set the backend responder name/command
        this.request = new JSONRequest();
        this.request.setResponder(responder_name);

        //default to add result to document body
        this.parentElement = document.body;
    }


    setParentElement(element) {
        this.parentElement = element;
    }

    setResponder(responder_name)
    {
        this.request.setResponder(responder_name);
    }

    /**
     * Call responder function 'render', initialize class,name,id,field for selector(), append result to this.parentElement
     * notify observers using EVENT_ELEMENT_CREATED
     */
    initialize()
    {
        //
        this.call("render", (result) => {
            try {
                const nodeList = document.templateFactory.nodeList(result.response.contents);

                //ensure response.contents root does not have siblings
                if (nodeList.length>1) {
                    throw "Expected nodeList length 1 - found " + nodeList.length;
                }

                this.element = nodeList[0];
                if (!(this.element instanceof HTMLElement)) throw "Node is not HTMLElement";

                //create the selector string
                this.class = ""+this.element.className;
                this.name = ""+this.element.getAttribute("name");
                this.id = ""+this.element.getAttribute("id");
                this.field = ""+this.element.getAttribute("field");

                document.templateFactory.appendNodes(this.parentElement, nodeList);

                const event_created = new SparkEvent(JSONComponent.EVENT_ELEMENT_CREATED, this);
                this.notify(event_created);

            }
            catch (e) {
                showAlert(e);
                throw e;
            }
        });
    }

    /**
     * Call responder function 'function_name' and assign request.onSuccess with 'fn'
     * @param function_name {string} Backend responder function name
     * @param fn {Function}
     */
    call(function_name, fn) {
        this.request.setFunction(function_name);
        this.request.onSuccess = fn;
        this.request.start();
    }

    remove() {
        this.element.remove();
    }


}