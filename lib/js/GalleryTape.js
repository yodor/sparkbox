function GalleryTape(cls)
{
  if (!cls) cls = ".GalleryTape";
  this.cls = cls;
  
  $(this.cls + " .button.left").on("click", function(){
    this.prevImage();
  }.bind(this));
  $(this.cls + " .button.right").on("click", function(){
    this.nextImage();
  }.bind(this));
  
//   console.log("GalleryTape for "+this.cls);
  
  this.duration_left = 500;
  this.duration_right = 500;
  
}
GalleryTape.prototype.connectGalleryView = function(relation)
{
  $(document).on("GalleryView", function(e){
      
//   console.log("GalleryTape::GalleryViewEventHandler EventRaltion: "+e.rel+" Message: "+e.message + " ConnectedRelation: "+relation);
      
  if (e.rel != relation) return;

  switch (e.message) {
    case "onPrevImage":
      this.prevImage();
      break;
    case "onNextImage":
      this.nextImage();
      break;

  }

	
  }.bind(this));
  
}

GalleryTape.prototype.nextImage = function()
{
  if ($(this.cls).data("execute"))return;
  
  $(this.cls).data("execute",true);

  var slots = $(this.cls + " .slots");
  var first = $(this.cls + " .slots .slot:first");
  
  var w = first.outerWidth(true);
  
  var duration = this.duration_right;

  first.animate({
    left: -w
  }, {
    duration: duration,
    step: function( now, fx ){
      $(this.cls + " .slot:gt(0)" ).css( "left", now );
    }.bind(this),
    complete: function() {

      first.appendTo(slots);
      
      $( this.cls + " .slot" ).css( "left", "" );
      $(this.cls).data("execute",false);
      
      $.event.trigger({
	    type: "GalleryTape",
	    message: "onNextImage",
	    time: new Date(),
      });
      
    }.bind(this)
  });
  

}
GalleryTape.prototype.prevImage = function()
{
  if ($(this.cls).data("execute"))return;
  
  $(this.cls).data("execute",true);
  
  var slots = $(this.cls + " .slots");
  var last = $(this.cls + " .slots .slot:last");
  
  var w = last.outerWidth(true);

  
  last.prependTo(slots);
  
  
  $( this.cls + " .slot" ).css( "left", -w );
  
  var duration = this.duration_left;
  
  var first = $(this.cls + " .slots .slot:first");
    first.animate({
    left: 0
  }, {
    duration: duration,
    step: function( now, fx ){
      $(this.cls + " .slot:gt(0)" ).css( "left", now );
    }.bind(this),
    complete:function() {
	$(this.cls).data("execute",false);
	  $.event.trigger({
	    type: "GalleryTape",
	    message: "prevImage",
	    time: new Date(),
	  });
	
    }.bind(this)
  });
  
    
}

