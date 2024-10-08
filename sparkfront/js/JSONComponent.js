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

            this.element = document.templateFactory.createElement(this.request.request_result.json_result.contents);

            this.parentElement.appendChild(this.element);

            const event_created = new SparkEvent(JSONComponent.EVENT_ELEMENT_CREATED, this);
            this.notify(event_created);
            document.dispatchEvent(event_created);

            const event_updated = new SparkEvent(SparkEvent.DOM_UPDATED);
            event_updated.source = this.parentElement;
            document.dispatchEvent(event_updated);

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