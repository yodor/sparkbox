function MCETextArea() 
{
  this.component_class = "TEXTAREA.MCETextArea";
  this.cls = this.component_class;
 
  this.image_browser = new MCEImageBrowserDialog();
  
  this.editor = null;
		
}

//user click on insert image button from toolbar
MCETextArea.prototype.onInsertImage = function(ed)
{

    this.editor = ed;
    
    //php side panels/MCEImageBrowserDialog
    if ( $("#mceImage_browser").get(0) ) {
      console.log("MCETextArea.prototype.onInsertImage() Custom image browser found using it");
      this.image_browser.mce = this;
      return this.image_browser.show(this);

    }

    console.log("Custom image browser not installed. #mceImage_browser not found in DOM");
    return true;

}

//init attach with specified element
MCETextArea.prototype.attachWith = function(name)
{
  
  if (!name) throw "Element name required for attachWith";
  
  this.cls = this.component_class + "[name='"+name+"']";
  this.name = name;
  console.log("MCETextArea::attachWith() field_name=["+name+"]");

  var mce_area = $(this.cls);
  
  if (mce_area.data("mce_init_done") == 1) {
    return;
  }
  
  var instance = this;
  
  mce_area.tinymce({
    // Location of TinyMCE script
    script_url : SITE_ROOT + 'lib/js/tiny_mce/tinymce.min.js',

    strict_loading_mode: true,
    theme: "modern",

    //
    entity_encoding : "raw",
    force_p_newlines : false,
    force_br_newlines : true,
    forced_root_block : false,// Needed for 3.x
        
  ///ver 4
    menubar: false,
    toolbar1: 'undo redo | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent blockquote',
    toolbar2: 'link unlink anchor image media code | insertdatetime preview | forecolor backcolor | mybutton | charmap ',
    plugins: 'link image lists charmap anchor insertdatetime media textcolor colorpicker',
    
    ///ver3
//     plugins : "insertdatetime,preview,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,media",
// 
//     theme_advanced_blockformats: "p,address,pre,h2,h3,h4,h5,h6,div",
//     theme_advanced_buttons1: "newdocument,|,cleanup,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,|,image,|,media",
//     theme_advanced_buttons2: "formatselect,fontselect,fontsizeselect,|,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
//     theme_advanced_buttons3: "",
//     theme_advanced_path_location: "bottom",
//     theme_advanced_resize_horizontal: false,
//     theme_advanced_resizing: 1,
//     theme_advanced_resizing_use_cookie: true,
//     theme_advanced_toolbar_align: "left",
//     theme_advanced_toolbar_location: "top",
    
    verify_html: 1,
    media_restrict: false,

    width: "100%",

    setup: function (editor) {
        editor.addButton('mybutton', {
            text: 'Server Image',
            icon: false,
            onclick: function () {
                //editor.insertContent('&nbsp;<b>It\'s my button!</b>&nbsp;');
                return instance.onInsertImage(editor);
            }
        });
    }

    
  });

  mce_area.data("mce_init_done", 1);

}
