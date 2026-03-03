class MCETextArea extends Component {

    constructor() {
        super();
        //all TEXTAREA elements having CSS class MCETextArea
        this.setClass(".MCETextArea TEXTAREA");

        this.image_browser = new MCEImageBrowserDialog();
        this.image_browser.setMCETextArea(this);

        this.editor = null;

    }


    defaultSetup() {
        return {
            schema: 'html5',

            extended_valid_elements: 'img[*],a[*]',

            strict_loading_mode: true,

            entity_encoding: "raw",

            // newline_behavior: 'linebreak',      // Enter → <br>; Shift+Enter also → <br>
            remove_trailing_brs: false,         // Optional: preserve trailing <br> if needed
            //forced_root_block: false,           // Explicitly disable block creation (pairs well with linebreak mode)


            menubar: false,
            toolbar1: 'undo redo | styles | fontfamily fontsize | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent blockquote',
            toolbar2: 'code|link unlink anchor | image media  | insertdatetime preview | forecolor backcolor | charmap | spark_imagebrowser | pageembed',
            plugins: 'link image lists charmap anchor insertdatetime media code',

            style_formats: [
                { title: 'Paragraph', block: 'p' },
                { title: 'Heading 1', block: 'h1' },
                { title: 'Heading 2', block: 'h2' },
                { title: 'Heading 3', block: 'h3' },
                { title: 'Heading 4', block: 'h4' },
                { title: 'Heading 5', block: 'h5' },
                { title: 'Heading 6', block: 'h6' }
            ],

            //invalid_elements: 'iframe,object,embed',

            resize: 'both',

            branding: false,

            verify_html: true,
            media_restrict: false,

            width: '100%',

            //content_css: "/mycontent.css",
            content_style: "p { margin: 0; } body { line-height: 1; }",

            license_key: 'gpl',

            relative_urls : false,
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

        mce_setup_object.setup = (editor) => {

            editor.ui.registry.addButton('spark_imagebrowser', {
                text: 'Image Browser',
                onAction: () => {
                    this.onInsertImage(editor);
                }
            });


            // editor.on("change keyup", (e) => {
            //     console.log('change keyup');
            //     editor.save(); // updates this instance's textarea
            // });

            editor.on('init', (event) => {
                this.onEditorInit(editor);
            });

            editor.on('BeforeSetContent', (e) => {

                e.content = this.cleanup(e.content);

            });

            editor.on('GetContent', (e) => {

                e.content = this.cleanup(e.content);

            });
        };


        mce_setup_object.selector = this.selector();

        //mce_area.tinymce(mce_setup_object);
        tinymce.init(mce_setup_object);

    }
    cleanup(content)
    {
        // Step 1: Replace real control characters (most common case)
        let processed = content
            .replace(/\r\n/g, '')     // Windows-style first
            .replace(/[\r\n]/g, '');  // Any remaining \r or \n

        // Step 2: If content was double-escaped or literal \\r\\n present (e.g., from JSON mishandling)
        processed = processed.replace(/\\r\\n|\\r|\\n/g, '');
        return processed;
    }
}