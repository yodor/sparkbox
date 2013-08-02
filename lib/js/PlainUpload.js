function PlainUpload()
{
  this.field = null;
  this.field_name = "";
  
}
PlainUpload.prototype.attachWith = function(name)
{
  
  this.field = $("body").find(".PlainUpload[field='"+name+"']").first();
  
  this.field_name = name;
  
  this.field.data("upload_control", this);
  
  var file_input = this.field.find("input[type='file']").first();

  file_input.change(function(event){  
        this.uploadFileChanged();
  }.bind(this));

}

PlainUpload.prototype.uploadFileChanged = function(event)
{
  console.log("Upload Changed");

  var file_input = this.field.find("input[type='file']").first();

  var value = file_input.get(0).value;
  
  this.field.find(".Details .Filename").html(value);
}

