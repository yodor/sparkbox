function toggleLinkedField(elm)
{
	if (!elm)return;
	var link_value = $(elm).attr("link_value");
	var field_name = $(elm).attr("name");

	if (!link_value)return;


	var value = $(elm).val();

	if (value == link_value) {
		$(".select_other_wrap[field='"+field_name+"']").attr("class","select_other_wrap");
	}
	else {
		$(".select_other_wrap[field='"+field_name+"']").attr("class","select_other_wrap hidden");
		$(".select_other_wrap[field='"+field_name+"'] INPUT").val("");
	}

}


jQuery.fn.extend({
  characterLimit: function(max_chars) {
    return this.each(function() {
	  
		var old_text = "";
		
		$(this).keydown(function() {
		  
			var current_text = $(this).val();
			
			var do_check = true;
			var KeyID = event.keyCode;
			switch(KeyID)
			{
				case 8:
	  // 			alert("backspace");
				  do_check = false;
				  break; 
				case 46:
	  // 			alert("delete");
				  do_check = false;
				  break;
				default:
				  break;
			}
			var info = $(this).parents().first().children("addon_content").find("info");
			var remaining = max_chars - current_text.length;
			
			if (info) {
			  info.html("Characters remaining: "+(remaining>0?remaining:0));
			}
			
			if (remaining<0 && do_check) {
			
			  $(this).val(old_text);
			  return false;
			}
			old_text = current_text;
			return true;
			
			
		});
		$(this).keyup(function(){
		  
			var current_text = $(this).val();
			
			var info = $(this).parents().first().children("addon_content").find("info");
			var remaining = max_chars - current_text.length;
			
			if (info) {

			  info.html("Characters remaining: "+(remaining>0?remaining:0));
			  
			}
			
			if (remaining<0 ) {
			
			  $(this).val(old_text);
			  return false;
			}
			return true;
		});
	  
	})
  }
});
// $("textarea[name='short_description']").wordLimit(150);
jQuery.fn.extend({
  wordLimit: function(max_words) {
    return this.each(function() {
		var old_text = "";
		
		$(this).keydown(function() {
			var do_check = true;
		
			var KeyID = event.keyCode;
			switch(KeyID)
			{
				case 8:
	  // 			alert("backspace");
				  do_check = false;
				  break; 
				case 46:
	  // 			alert("delete");
				  do_check = false;
				  break;
				default:
				  break;
			}
			var current_text = $(this).val();

			var arr = current_text.match(/\w+|"[^"]+"/g);
			var info = $(this).parents().first().children("addon_content").find("info");
			var remaining = max_words;
			if (arr) {
			  remaining = (max_words-arr.length); 
			}
			if (info) {
			  info.html("Words remaining: "+(remaining>0?remaining:0));
			}
			
			if (remaining<0 && do_check) {
			
			  $(this).val(old_text);
			  return false;
			}
			old_text = current_text;
			return true;
	  
		});
		
		$(this).keyup(function(){
			var current_text = $(this).val();

			var arr = current_text.match(/\w+|"[^"]+"/g);
			var info = $(this).parents().first().children("addon_content").find("info");
			var remaining = max_words;
			if (arr) {
			  remaining = (max_words-arr.length); 
			  
			}
			if (info) {

			  info.html("Words remaining: "+(remaining>0?remaining:0));
			}
			
			if (remaining<0 ) {
			
			  $(this).val(old_text);
			  return false;
			}
			return true;
		
		});
    });
  }
  
});