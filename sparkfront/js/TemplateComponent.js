/**
 * Load template contents to DOM
 * if templateID is null uses the JavaScript object class name as templateID
 */
class TemplateComponent extends Component
{
    constructor(templateID=null) {
        super();
        if (!templateID) {
            templateID = this.constructor.name;
        }

        this.element = document.templateFactory.getElement(templateID).cloneNode(true);
        this.class = ""+this.element.className;
        this.name = ""+this.element.getAttribute("name");
        this.field = ""+this.element.getAttribute("field");

    }

    initialize() {
        //initialization is already done in the constructor
    }

    render() {
        document.body.appendChild(this.element);

        const event_update = new SparkEvent(SparkEvent.DOM_UPDATED);
        event_update.source = this.element;
        document.dispatchEvent(event_update);
    }

    remove() {
        this.element.remove();
    }
}