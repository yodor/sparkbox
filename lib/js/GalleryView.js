
addLoadEvent(function(){
  
    var gallery_view = new GalleryView();
  
    $("BODY").find("A.image_popup").live("click", function(){
	gallery_view.popupImage($(this));
	return false;
    });

    $(document).on("GalleryView", function(e){

	switch (e.message) {

	  case "onClickPane":
	    gallery_view.onClickPane(e);
	    break;
	  case "onClickImage":
	    gallery_view.onClickImage(e);
	    break;
	  
	}
	
    }.bind(this));
  
});


function GalleryView()
{
  this.pos = -1;

  this.collection = new Array();
  
  this.related = "";
  //console.log("ImagePopup::popupContents Collection Lenght: " + this.collection.length +" Current Position: "+this.pos);
  
  this.modal_pane = new ModalPopup();
  
  this.margin_width = 100;
  this.margin_height = 100;
  
  $.event.trigger({
    type: "GalleryView",
    message: "onCreate",
    time: new Date(),
    object: this,
    instance: this
  });
}
GalleryView.prototype.onClickImage = function(event)
{
  
//   console.log("Default Handler: onClickImage");
  this.closeImagePopup();
  
}
GalleryView.prototype.onClickPane = function(event)
{
  
//   console.log("Default Handler: onClickPane");
  this.closeImagePopup();
  
}
GalleryView.prototype.nextImage = function(event)
{
  event.stopPropagation();
  
  var old_position = this.pos;
  
  this.pos++;
  if (this.pos>=this.collection.length)this.pos=0;
  this.startImageLoader();

  $.event.trigger({
    type: "GalleryView",
    message: "onNextImage",
    time: new Date(),
    rel: this.related,
    position: this.pos,
    old_position: old_position,
    event_source: event
  });

}

GalleryView.prototype.prevImage = function(event)
{
  event.stopPropagation();
  
  var old_position = this.pos;
  
  this.pos--;
  if (this.pos<0)this.pos=this.collection.length-1;

  this.startImageLoader();
  
  $.event.trigger({
    type: "GalleryView",
    message: "onPrevImage",
    time: new Date(),
    rel: this.related,
    position: this.pos,
    old_position: old_position,
    event_source: event
  });

}
GalleryView.prototype.popupImage = function(aelm)
{
  
  var related = aelm.attr("rel");
  
  
  
  if (related) {
    this.collection = $("A.image_popup[rel='"+related+"']");
    this.related = related;
  }
  else {
    this.collection = aelm;
  }

  this.pos = this.collection.index(aelm);

  
  this.modal_pane.showContent(this.createPopupContents());
  
  this.modal_pane.paneClicked = function(event){
      
      $.event.trigger({
      type: "GalleryView",
      message: "onClickPane",
      time: new Date(),
      rel: this.related,
      position: this.pos,
      event_source: event,
      });
      
  }.bind(this);
    

    
    var buttonNext = this.modal_pane.popup().find("[action='NextImage']");
    buttonNext.unbind("click");
    buttonNext.bind("click", function(event){
      this.nextImage(event);
    }.bind(this));
    
    var buttonPrev = this.modal_pane.popup().find("[action='PrevImage']");
    buttonPrev.unbind("click");
    buttonPrev.bind("click", function(event){
      this.prevImage(event);
    }.bind(this));
    
    var buttonClose = this.modal_pane.popup().find("[action='CloseImagePopup']");
    buttonClose.unbind("click");
    buttonClose.bind("click", function(event){
      this.closeImagePopup(event);
    }.bind(this));
    
    this.startImageLoader();
    
    $.event.trigger({
      type: "GalleryView",
      message: "onShowImagePopup",
      time: new Date(),
      rel: this.related,
      position: this.pos
    });
    
    
}

GalleryView.prototype.createPopupContents = function()
{
  var html = "<div class='PopupPanel ImagePopupPanel'><div class='Inner'>";
      html += "<div class='Contents'>";
	
	html += "<a class='Button' action='CloseImagePopup'></a>";
	
	html += "<div class='header_caption'></div>";
	
	html += "<img class='ImagePart'><a class='Button' action='PrevImage'></a><a class='Button' action='NextImage'></a>";
	
	html += "<div class='footer_caption'></div>";
      
      html += "</div>";//contents
      html += "</div></div>";
      
  return this.processPopupContents(html);
}
GalleryView.prototype.processPopupContents = function(html)
{
  return html;
  
}
GalleryView.prototype.closeImagePopup = function()
{

  
  $.event.trigger({
    type: "GalleryView",
    message: "onCloseImagePopup",
    time: new Date(),
    rel: this.related,
    position: this.pos
  });
  
  this.modal_pane.pane().remove();
}

GalleryView.prototype.fetchImage = function()
{
  
  
  var aelm = this.collection.slice(this.pos,this.pos+1);

  if (!aelm) {
    console.log("Requested position: "+this.pos+" out of bounds for this image collection.");
    return;
  }
  
  var href = aelm.attr("href");
  //console.log("ImagePopup::fetchImage["+this.pos+"] Fetching Image URL:"+href);

  
  var screen_width = $(window).width();
  var screen_height = $(window).height();
  
  
  if (href.indexOf("?")!=-1) href = href + "&max-width="+(screen_width-this.margin_width)+"&max-height="+(screen_height-this.margin_height);

	  
  var fetch_image = new Image();
  
  fetch_image.onload = function() {

      //console.log("ImagePopup::fetchImage["+this.pos+"] Finished Loading URL:"+href);

      var img = this.modal_pane.popup().find(".Contents .ImagePart");
      
      img.unbind("load");
      
      img.bind("load", function(event){

	  
	  if (aelm.attr("footer_caption")) {
	    if (this.modal_pane.popup().find(".Contents .footer_caption").get(0)) {
		this.modal_pane.popup().find(".Contents .footer_caption").html(aelm.attr("footer_caption"));
		this.modal_pane.popup().find(".Contents .footer_caption").css("max-width", img.width());
	    }
	    
	  }
	  
	  
	  if (this.collection.length>1) {
	     this.modal_pane.popup().find(".Contents .Button").css("visibility", "visible");
	  }

	  
	  
	  
	  $.event.trigger({
	    type: "GalleryView",
	    message: "onFetchImage",
	    time: new Date(),
	    rel: this.related,
	    position: this.pos,
	    
	  });
	  
	  
	  this.modal_pane.centerContents();
	  this.modal_pane.popup().find(".Contents ").removeAttr("working");
	  this.modal_pane.popup().find(".Contents").css("visibility", "visible");
	  
	  setTimeout(function(){
	    
	   
	    
	    
	  },100);
// 	  console.log(aelm);
	  
      }.bind(this));
      
      img.bind("click", function(event) {
	  event.stopPropagation();
	  $.event.trigger({
	    type: "GalleryView",
	    message: "onClickImage",
	    time: new Date(),
	    rel: this.related,
	    position: this.pos,
	  });
	
      }.bind(this));
      
      this.modal_pane.popup().find(".Contents").css("visibility", "hidden");
      img.attr("src", fetch_image.src);
	
  }.bind(this);
  fetch_image.src = href;
  
}
GalleryView.prototype.startImageLoader = function()
{

  this.modal_pane.popup().find(".Contents ").attr("working",1);
  
  var img = this.modal_pane.popup().find(".Contents .ImagePart");
  
  img.unbind();
  img.bind("load", function(){
      this.modal_pane.centerContents();

      setTimeout(function(){
	this.fetchImage();
      }.bind(this),100);
  }.bind(this));
  img.attr("src", ajax_loader_src);
}

