
function MessageDialog(id)
{
  this.text = "";
  this.caption = "Message";
  
  this.modal_pane = new ModalPopup();
  this.id = "message_dialog";
  if (id) {
    this.id = id;
    
  }
  this.idx = 0;
  this.icon_enabled = true;
}
MessageDialog.prototype.createContent = function()
{
  this.idx++;
  
  var cnt = $("#"+this.id).clone();
  
  if (this.id) {
    cnt.attr("id", this.id+"_"+this.idx);
  }
  if (this.caption) {
    cnt.find(".caption_text").html(this.caption);
  }
  if (this.text) {
    cnt.find(".message_text").html(this.text);
  }
  if (!this.icon_enabled) {
    cnt.find(".message_icon").remove();
    
  }
  return cnt.get(0).outerHTML;
}
MessageDialog.prototype.setCaption = function(text)
{
  this.caption = text;
}
MessageDialog.prototype.setText = function(text)
{
  this.text = text;
}
MessageDialog.prototype.setID = function(id)
{
  this.id = id;
}
MessageDialog.prototype.show = function()
{

    this.modal_pane.showContent(this.createContent());
    

    
  
}