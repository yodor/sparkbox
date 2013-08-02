function BeanTranslationDialog()
{
 
  this.req = new JSONRequest();
  this.langID = -1;
  this.field_name = "";
  this.bean_class = "";
  this.bean_id = "";
  this.req.async = true;
  this.language_alert = "Please select translation language";

  this.modal_pane = new ModalPopup();
  
}

BeanTranslationDialog.prototype.attachWith = function(panel_id)
{
  if (!panel_id) {
    showAlert("BeanTranslationDialog need panel_id to attach with");
    
  }
  this.cls = panel_id;
  
  
}

BeanTranslationDialog.prototype.show = function(field_name)
{
  
  this.modal_pane.showID(this.cls);
  
  
  
//     bean_id, field_name, bean_class
  var bean_id = $(".InputFormView").attr("editID");
  var bean_class = $(".InputFormView").attr("bean");
    
  

  var popup = this.modal_pane.popup();
  
  popup.find("[action]").unbind("click");
  
  popup.find("[action='Translate']").click(function(){
      this.store();
  }.bind(this));
  
  popup.find("[action='Clear']").click(function(){
      this.clear();
  }.bind(this));
  
  popup.find("[action='Close']").click(function(){
      this.finish();
  }.bind(this));
  
  popup.find("[name='langID']").change(function(){
      this.changeLanguage();
  }.bind(this));
  
  popup.find(".caption [action='close']").click(function(){
      this.finish();
  }.bind(this));


  popup.find(".original_text").val($("FORM [name='"+field_name+"']").val());

  popup.find("[name='translation']").attr("readonly", true);
    
  this.field_name = field_name;
  this.bean_class = bean_class;
  this.bean_id = bean_id;

  this.req.progress_display = popup.find(".AjaxProgress");
  
  //check for last selected language and use it
  var last_langID = $(window).data("langID");
  
  if (last_langID>0) {
    popup.find("[name='langID']").val(last_langID);
    this.changeLanguage(last_langID);

  }

}

BeanTranslationDialog.prototype.store = function()
{

  var popup = this.modal_pane.popup();

  var frm = popup.find("form").get(0);
  
  this.req.setURL("?ajax=1&cmd=bean_translator&type=store&langID="+this.langID+"&field_name="+this.field_name+"&beanID="+this.bean_id+"&bean_class="+this.bean_class);
  
  this.req.post_data = formtostr(frm);
 
  this.req.start(
    function(request_result) {
      showAlert(request_result.json_result.message);
    }
  );

}

BeanTranslationDialog.prototype.changeLanguage = function(langID)
{
 
  if (!langID) langID = this.modal_pane.popup().find("[name='langID']").val();
  
  this.langID = parseInt(langID);
  
  var translation_area = this.modal_pane.popup().find("[name='translation']");
  
  translation_area.val("");
  translation_area.attr("readonly", true);
  
  $(window).data("langID", this.langID);
  
  if (this.langID<1) {
    return;
  }

  
  this.fetch();
  
}

BeanTranslationDialog.prototype.fetch = function()
{
    if (this.langID<1) {
      	showAlert(this.language_alert);
	return;
    }

    var url = "?ajax=1&cmd=bean_translator&type=fetch&langID="+this.langID+"&field_name="+this.field_name+"&beanID="+this.bean_id+"&bean_class="+this.bean_class;

    this.req.setURL(url);

    this.req.post_data = null;
    this.req.start(

      function (request_result) {
	
	var result = request_result.json_result;
	var popup = this.modal_pane.popup();
	popup.find("[name='translation']").val(result.translation);
	popup.find("[name='translation']").attr("readonly", false);
	if (!result.translation)showAlert(result.message);

      }.bind(this)

    );

}

BeanTranslationDialog.prototype.clear = function()
{
    if (this.langID<1) {
      	showAlert(this.language_alert);
	return;
    }
    
    this.req.setURL("?ajax=1&cmd=bean_translator&type=clear&langID="+this.langID+"&field_name="+this.field_name+"&beanID="+this.bean_id+"&bean_class="+this.bean_class);

    this.req.post_data = null;
    this.req.start(
      function (request_result) {
	  var result = request_result.json_result;
	  var popup = this.modal_pane.popup();
	  popup.find("[name='translation']").val("");
	  popup.find("[name='translation']").attr("readonly", false);
	  showAlert(result.message);
      }.bind(this)
    );
}

BeanTranslationDialog.prototype.finish = function()
{
//   closePopup("translator_popup",true);
  this.modal_pane.pane().remove();
  
  $(".BeanTranslationPopup").first().find("[name='langID'] option[value='"+this.langID+"']").attr("selected",true);
  
}




