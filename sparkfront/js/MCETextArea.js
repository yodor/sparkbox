class MCETextArea extends Component {

    constructor() {
        super();
        //all TEXTAREA elements having CSS class MCETextArea
        this.setClass("TEXTAREA.MCETextArea");

        this.image_browser = new MCEImageBrowserDialog();
        this.image_browser.setMCETextArea(this);

        this.editor = null;

    }

    onEditorInit(editor) {
        this.editor = editor;
        console.log("MCETextArea::onEditorInit() - Using editor ID: " + editor.id);
    }

    onInsertImage(ed) {
        this.editor = ed;
        this.image_browser.show();
    }

    initialize() {

        super.initialize();

        this.image_browser.initialize();

        console.log("MCETextArea::initialize() - Using selector: " + this.selector());
        let mce_area = $(this.selector());

        let instance = this;

        mce_area.tinymce({

            schema: 'html5',

            extended_valid_elements: 'img[*],a[*]',

            // Location of TinyMCE script
            script_url: SPARK_LOCAL + '/js/tiny_mce/tinymce.min.js',

            strict_loading_mode: true,
            theme: "silver",

            //
            entity_encoding: "raw",
            force_p_newlines: true,
            force_br_newlines: true,

            ///ver 4
            menubar: false,
            toolbar1: 'code | undo redo | fontselect fontsizeselect | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent blockquote',
            toolbar2: 'link unlink anchor | image media code | insertdatetime preview | forecolor backcolor | charmap | spark_imagebrowser |',
            plugins: 'code link image lists charmap anchor insertdatetime media paste code',

            resize: 'both',

            branding: false,

            verify_html: 1,
            media_restrict: false,

            width: '100%',
            height: '300px',

            setup: function (editor) {

                editor.ui.registry.addButton('spark_imagebrowser', {
                    text: 'Image Browser',
                    onAction: () => {
                        instance.onInsertImage(editor);
                    }
                });


                // editor.on("change keyup", function (e) {
                //     console.log('change keyup');
                //     editor.save(); // updates this instance's textarea
                // });


                editor.on('init', function (event) {
                    instance.editor = editor;
                    instance.onEditorInit(editor);

                });
            }


        });


    }
}

