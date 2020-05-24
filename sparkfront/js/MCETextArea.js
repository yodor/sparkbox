class MCETextArea extends Component {
    constructor() {
        super();
        //all TEXTAREA elements having CSS class MCETextArea
        this.setClass("TEXTAREA.MCETextArea");

        this.image_browser = new MCEImageBrowserDialog();
        this.image_browser.setMCETextArea(this);

        this.editor = null;

        this.onEditorInit = function (editor) {

            console.log("MCETextArea::onEditorInit() - '" + editor.id + "' initialized");

        };


    }

    onInsertImage(ed) {
        this.editor = ed;
        this.image_browser.show();
    }

    initialize() {

        super.initialize();

        this.image_browser.initialize();

        var mce_area = $(this.selector());

        if (mce_area.data("mce_init_done") == 1) {
            console.log("MCETextArea::attachWith() - init already done");
            return;
        }

        var instance = this;

        mce_area.tinymce({


            // Location of TinyMCE script
            script_url: SPARK_LOCAL + '/js/tiny_mce/tinymce.min.js',

            strict_loading_mode: true,
            theme: "silver",

            //
            entity_encoding: "raw",
            force_p_newlines: false,
            force_br_newlines: true,

            ///ver 4
            menubar: false,
            toolbar1: 'code | undo redo | fontselect fontsizeselect | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent blockquote',
            toolbar2: 'link unlink anchor image media code | insertdatetime preview | forecolor backcolor | mybutton | charmap ',
            plugins: 'code link image lists charmap anchor insertdatetime media textcolor colorpicker',

            resize: 'both',

            branding: false,

            verify_html: 1,
            media_restrict: false,

            width: '100%',

            setup: function (editor) {

                instance.editor = editor;

                editor.ui.registry.addButton('mybutton', {
                    text: 'Server Image',

                    onAction: () => {

                        instance.onInsertImage(editor);
                    }
                });


                // editor.on("change keyup", function (e) {
                //     console.log('change keyup');
                //     editor.save(); // updates this instance's textarea
                // });


                editor.on('init', function (e) {
                    instance.editor = editor;

                    instance.onEditorInit(editor);

                });
            }


        });

        mce_area.data("mce_init_done", 1);
    }
}

