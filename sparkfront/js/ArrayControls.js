class ArrayControls extends Component {


    constructor() {
        super();
        this.setClass(".ArrayControls");
    }

    //uses field attribute
    initialize() {

        super.initialize();

        var controls = $(this.selector());
        controls.find("[action='Add']").first().click(function (event) {

            this.insertField();

        }.bind(this));

        var field = $(this.selector()).parents().first();

        var instance = this;

        var array_contents = field.children(".ArrayContents");
        array_contents.find("[action='Remove']").each(function (index) {

            $(this).unbind("click");
            $(this).bind("click", function (event) {

                instance.removeField(this);

            });

        });

    }

    insertField() {

        var field = $(this.selector()).parents().first();

        var source_renderer = field.children(".ElementSource");
        var array_contents = field.children(".ArrayContents");

        var element_count = array_contents.children().length;

        var element = source_renderer.clone();

        element.find("[field='render_source']").attr("field", this.field);

        let field_name = this.field;

        element.find("[name]").each(function (col) {

            var name = $(this).attr("name");
            name = name.replace("render_source", field_name + "[" + element_count + "]");
            $(this).attr("name", name);


        });

        element.attr("class", "Element");

        element.attr("pos", element_count);

        array_contents.append(element);

        var instance = this;

        array_contents.find("[action='Remove']").each(function (index) {

            $(this).unbind("click");
            $(this).bind("click", function (event) {

                instance.removeField(this);

            });

        });

    }

    removeField(btn) {
        var array_contents = $(btn).parents(".ArrayContents").first();
        var field_name = array_contents.attr("field");

        var current_position = $(btn).parents(".Element").first().attr("pos");

//   console.log("Will remove position: "+current_position);

        array_contents.children(".Element[pos='" + current_position + "']").remove();

        //reposition elements
        array_contents.children(".Element").each(function (pos) {

            $(this).attr("pos", pos);

            var input_field = $(this).children(".InputField");

            input_field.attr("field", field_name + "[" + pos + "]");

            input_field.find("[name]").each(function (index) {

                var append_name = ($(this).attr("name").endsWith("[]")) ? "[]" : "";

                $(this).attr("name", field_name + "[" + pos + "]" + append_name);

            });
        });


    }

}