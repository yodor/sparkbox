class TemplateComponent extends Component
{
    constructor() {
        super();
    }

    initialize() {
        this.element = document.templateFactory.getElement(this.id);
        this.class = ""+this.element.className;
        this.name = ""+this.element.getAttribute("name");
        this.field = ""+this.element.getAttribute("field");
        console.log(this);
    }
}