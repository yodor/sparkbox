class JSONComponent extends Component {
    static EVENT_ELEMENT_CREATED = "element_created";

    constructor(name) {
        super();

        //set the backend responder name/command
        this.name = name;

        this.request = new JSONRequest();

        const observer = this.onRequestEvent.bind(this);

        //observe completion of request
        this.request.addObserver(observer);

        //default to add result to document body
        this.parentElement = document.body;
    }

    onRequestEvent(event) {

        if (event.isEvent(JSONRequest.EVENT_SUCCESS)) {

            const nodeList = document.templateFactory.appendContent(this.parentElement, this.request.request_result.json_result.contents);

            //ensure json_result.contents root is single
            if (nodeList.length>1) {
                showAlert("NodeList count > 1");
                throw "NodeList count > 1";
            }

            this.element = nodeList[0];

            const event_created = new SparkEvent(JSONComponent.EVENT_ELEMENT_CREATED, this);
            this.notify(event_created);
            document.dispatchEvent(event_created);

        }

    }

    setParentElement(element) {
        this.parentElement = element;
    }

    initialize()
    {
        this.request.setResponder(this.name);

    }

    call(function_name) {
        this.request.setFunction(function_name);
        this.request.start();
    }

    remove() {
        this.element.remove();
    }
}