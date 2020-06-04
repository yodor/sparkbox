class ArrayField extends Component {


    constructor() {
        super();
        this.setClass(".ArrayField");
        this.array_controls = null;
        this.array_contents = null;
        this.element_source = null;

    }

    //uses field attribute
    initialize() {

        super.initialize();

        let instance = this;

        this.array_controls = $(this.selector() + " .ArrayControls");
        this.array_controls.find("[action='Add']").first().on("click", this.insertItem.bind(this));

        this.array_contents = $(this.selector() + " .ArrayContents");
        this.array_contents.find("[action='Remove']").each(function (index) {

            $(this).removeAttr("href");
            $(this).on("click", function(event){
                instance.removeItem(this);
            });

        });

        this.element_source = $(this.selector() + " .ElementSource");
    }

    insertItem() {

        let element_count = this.array_contents.children().length;

        let element = this.element_source.clone(true,true);

        element.find("[field='render_source']").attr("field", this.field);

        let field_name = this.field;

        element.find("[name]").each(function (col) {

            let name = $(this).attr("name");
            name = name.replace("render_source", field_name + "[" + element_count + "]");
            $(this).attr("name", name);

        });

        element.attr("class", "Element");

        element.attr("pos", element_count);

        this.array_contents.append(element);

        let instance = this;

        this.array_contents.find("[action='Remove']").each(function (index) {
            $(this).removeAttr("href");
            $(this).on("click", function(event){
                instance.removeItem(this);
            });
        });

    }

    /**
     *
     * @param btn Corresponding element 'Remove' button
     */
    removeItem(btn) {



        let position = $(btn).parents(".Element").first().attr("pos");

        console.log("Removing element at position: " + position);

        this.array_contents.find(".Element[pos='" + position + "']").remove();

        let field_name = this.field;

        //reposition elements
        this.array_contents.children(".Element").each(function (pos) {

            $(this).attr("pos", pos);

            $(this).find("[name]").each(function (index) {

                let append_name = ($(this).attr("name").endsWith("[]")) ? "[]" : "";

                $(this).attr("name", field_name + "[" + pos + "]" + append_name);

            });
        });


    }

}