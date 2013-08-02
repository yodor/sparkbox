

function changeRating(e, elm)
{
  var value = $(elm).attr("value");
  
  updateValue($(elm).parent().get(), value);
  
}
function updateValue(elm,value)
{
  $(elm).children().each(function(index){
	  if ($(this).attr("value")<=value) {
		$(this).attr("class","star on");
		
	  }
	  else {
		$(this).attr("class","star off");
		
	  }
	
  });
}
function ratingLeave(e, elm)
{
	var value = $(elm).attr("value");
// 	alert("Current Value: "+value);
	updateValue(elm, value);
	
}

addLoadEvent(function(){


  $('.rating_stars').each(function(index) {
	
	$(this).mouseenter(function(e){
		ratingOver(e, this);
		
	});
	
	$(this).children().each(function(index){
	  
		$(this).mouseenter(function(e){
		
		  changeRating(e, this);
		
		});
		$(this).click(function(e){
		  
		  postRating(e, this);
		});
	});
	
	
	$(this).mouseleave(function(e){
		hideTooltip();
		ratingLeave(e, this);
	});
  });

});