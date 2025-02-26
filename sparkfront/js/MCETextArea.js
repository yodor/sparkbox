class MCETextArea extends Component {

    constructor() {
        super();
        //all TEXTAREA elements having CSS class MCETextArea
        this.setClass(".MCETextArea TEXTAREA");

        this.image_browser = new MCEImageBrowserDialog();
        this.image_browser.setMCETextArea(this);

        this.editor = null;

    }

    /**
     * Overriden in MCESetupObject.js
     * @returns {{schema: string, strict_loading_mode: boolean, convert_newlines_to_brs: boolean, plugins: string, branding: boolean, verify_html: number, media_restrict: boolean, script_url: string, force_br_newlines: boolean, invalid_elements: string, forced_root_block: boolean, newline_behavior: string, menubar: boolean, toolbar2: string, content_style: string, toolbar1: string, force_p_newlines: boolean, remove_linebreaks: boolean, width: string, resize: string, theme: string, entity_encoding: string, extended_valid_elements: string, height: string}}
     */
    defaultSetup() {
        return {
            schema: 'html5',

            extended_valid_elements: 'img[*],a[*]',

            // Location of TinyMCE script
            script_url: SPARK_LOCAL + '/js/tiny_mce/tinymce.min.js',

            strict_loading_mode: true,
            theme: "silver",

            //
            entity_encoding: "raw",

            // force_p_newlines: false,
            force_br_newlines: true,
            convert_newlines_to_brs: true,
            remove_linebreaks: true,
            // forced_root_block: false,
            newline_behavior: 'linebreak',

            ///ver 4
            menubar: false,
            toolbar1: 'undo redo | styles | fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent blockquote',
            toolbar2: 'code|link unlink anchor | image media  | insertdatetime preview | forecolor backcolor | charmap | spark_imagebrowser',
            plugins: 'link image lists charmap anchor insertdatetime media code',

            style_formats: [
                { title: 'Heading 1', block: 'h1' },
                { title: 'Heading 2', block: 'h2' },
                { title: 'Heading 3', block: 'h3' },
                { title: 'Heading 4', block: 'h4' },
                { title: 'Heading 5', block: 'h5' },
                { title: 'Heading 6', block: 'h6' }
            ],

            invalid_elements: 'iframe,object,embed',

            resize: 'both',

            branding: false,

            verify_html: true,
            media_restrict: false,

            width: '100%',

            //content_css: "/mycontent.css",
            content_style: "p { margin: 0; } body { line-height: 1; }",

            //ver 7
            license_key: 'gpl',
        };

    }

    onEditorInit(editor) {
        this.editor = editor;
        console.log("MCETextArea::onEditorInit() - Using editor ID: " + editor.id);
    }

    onInsertImage(editor) {

        //create new instance - clone fresh template

        this.image_browser.show();
    }

    initialize() {

        super.initialize();

        console.log("MCETextArea::initialize() - Using selector: " + this.selector());

        let mce_setup_object = null;

        if (typeof getMCESetupObject === "function") {
            mce_setup_object = getMCESetupObject();
        }
        else {
            mce_setup_object = this.defaultSetup();
        }

        let instance = this;

        mce_setup_object.setup = function (editor) {

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
                instance.onEditorInit(editor);
            });
        };

        mce_setup_object.selector = this.selector();

        //mce_area.tinymce(mce_setup_object);
        tinymce.init(mce_setup_object);


    }
}
