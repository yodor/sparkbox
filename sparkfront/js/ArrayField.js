class ArrayField extends Component {


    constructor() {
        super();
        this.setClass(".ArrayField");
        /**
         *
         * @type {Element}
         */
        this.array_controls = null;
        /**
         *
         * @type {Element}
         */
        this.array_contents = null;
        /**
         *
         * @type {Element}
         */
        this.element_source = null;

    }

    //uses field attribute
    initialize() {

        super.initialize();

        let instance = this;

        this.array_controls = this.element.querySelector(".ArrayControls");
        this.array_controls.querySelector("[action='Add']").addEventListener("click", (event)=>this.insertItem(event));


        this.array_contents = this.element.querySelector(".ArrayContents");
        this.array_contents.querySelectorAll("[action='Remove']").forEach((element)=>{
            element.addEventListener("click", (event)=>this.removeItem(element));
        });

        this.element_source = this.element.querySelector(".ElementSource");
    }

    insertItem(event) {

        const element_count = this.array_contents.children.length;

        let element_source = this.element_source.cloneNode(true);
        element_source.className = "Element";
        element_source.setAttribute("field", this.field);
        element_source.setAttribute("pos", element_count);

        element_source.querySelectorAll("[name]").forEach((element)=>{
            const element_name = this.field + "[" + element_count + "]";
            element.setAttribute("name", element_name);
        });

        const remove_button = element_source.querySelector("[action='Remove']");
        remove_button.addEventListener("click", (event)=>this.removeItem(remove_button));

        this.array_contents.append(element_source);

        const ev = new SparkEvent(SparkEvent.DOM_UPDATED);
        ev.source = this.array_contents;
        document.dispatchEvent(ev);
    }

    /**
     *
     * @param element {HTMLElement} Remove button of the element being removed from ArrayContents
     */
    removeItem(remove_button) {

        const element = remove_button.closest(".Element");

        let position = element.getAttribute("pos");
        console.log("Removing element at position: " + position);

        element.remove();

    }

}