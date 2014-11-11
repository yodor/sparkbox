
function MenuBarComponent()
{
  this.component_class = ".MenuBarComponent[submenu_popup!=0]";
  this.cls = this.component_class;
}

MenuBarComponent.prototype.attachWith = function(name)
{
  this.name = name;
  if (name) {
    this.cls = this.component_class + "[name='"+name+"']";
  }
  
  var instance = this;
  
  $(this.cls).first().find(" .MenuBarItemRenderer").each(function(index) {
	$(this).on("mouseenter", function(event){
		event.preventDefault();
		instance.menuEnter(this);
		return false;
	});
	
	$(this).on("touchstart", function(event){
		event.preventDefault();
		
		if ( $(this).data("hover") || $(this).children(".SubmenuRenderer").first().length == 0 ) {
			instance.setItemSelected(this, true);
			var link = $(this).find("A").first();
			var href = link.attr("href");
			window.location.href=href;
		}
		else {
			instance.leaveAll();
			instance.menuEnter(this);
		}
		
		return false;
	});
	
	
	$(this).on("mouseleave", function(event){
		event.preventDefault();
		instance.menuLeave(this);
		return false;
	});

	$(this).on("click touch", function(event) {
		event.preventDefault();
		var link = $(this).find("A").first();
		var href = link.attr("href");
		window.location.href=href;
		return false;
	});
	
  });

  $(this.cls).first().find(" .SubmenuRenderer").each(function(index) {
       $(this).on("click touch", function(event){
		  event.preventDefault();
		  return false;
	
      });
  });
  
  $(this.cls).first().find(" .SubmenuItemRenderer").each(function(index) {
	
	$(this).on("mouseenter", function(event){
		event.preventDefault();
		
		instance.submenuEnter(this, event);
		
		return false;
	});
	
	$(this).on("touchstart", function(event){
		event.preventDefault();

		if ( $(this).data("hover") || $(this).children(".SubmenuRenderer").first().length == 0 ) {
		  
		  instance.setItemSelected(this, true);
		  
		  var link = $(this).find("A").first();
		  var href = link.attr("href");
		  window.location.href=href;
			
		}
		else {
		  
		  instance.submenuEnter(this, event);
		  
		}
		return false;
	});
	
	$(this).on("click", function(event){
		
		event.preventDefault();
		var link = $(this).find("A").first();
		var href = link.attr("href");
		window.location.href=href;
		return false;
	});


  });
  
}
MenuBarComponent.prototype.leaveAll = function()
{
  var instance = this;

  $(this.cls).find(" .MenuBarItemRenderer").each(function(index) {
	  var mitem = $(this);
	  instance.menuLeave(mitem);
  });
}
MenuBarComponent.prototype.setItemSelected = function (elm, mode)
{
  if (!elm) return;
  
  var css_class = $(elm).attr("class");
  css_class = css_class.replace("Hover", "");
  
  if (mode) {
    css_class = css_class + " Hover";
  }
  
  $(elm).attr("class", trim(css_class));
  
  $(elm).data("hover", mode);
  
}


MenuBarComponent.prototype.menuEnter = function(mitem)
{
//     var source = $(mitem).parents("[source]").last().attr("source");
// 
    this.setItemSelected(mitem,true);

    var have_submenu = $(mitem).attr("have_submenu");
    
    if (have_submenu) {

      var pos = $(mitem).offset();
      var y = pos.top + $(mitem).outerHeight(true);
      
      this.submenuShow(mitem,  pos.left, y );

    }
}

MenuBarComponent.prototype.menuLeave = function(mitem)
{
  
  this.setItemSelected(mitem, false);
  
  var instance = this;
  
  $(mitem).find(".SubmenuRenderer").each(
      function(index) {
		  $(this).css("display", "none");

		  $(this).find(".SubmenuItemRenderer").each(
			function(index1) {

				instance.setItemSelected($(this).get(0), false);

			}
		  );
	
      }
  );
}	

//
MenuBarComponent.prototype.submenuEnter = function(smitem, event)
{

  var pos = $(smitem).offset();
  var h = $(smitem).outerHeight(true);
  var v = $(smitem).outerWidth(true);

  var instance = this;
 
  $(smitem).parent(".SubmenuRenderer").find(".SubmenuItemRenderer").each(
    function(index){
		
       if ($(this).get(0) === smitem) return;
									 
       instance.setItemSelected($(this).get(0), false);
       
       $(this).find(".SubmenuRenderer").each(
			function(index1)
			{
				$(this).css("display", "none");
			}
		);
    }
  );

  this.submenuShow(smitem, (pos.left + v ), pos.top);

  this.setItemSelected(smitem, true);
}




MenuBarComponent.prototype.submenuShow = function(mitem, left, top)
{

    
    var layout_direction = $(mitem).attr("submenu_direction");
    
    var submenu = $(mitem).children(".SubmenuRenderer").first();
    
//     submenu.css("min-width", menu_item.outerWidth(true));

    var margin_top = parseInt(submenu.css("margin-top"));
    if (!isNaN(margin_top) && margin_top>0) {
      top+=margin_top;
    }
    
    submenu.css("display", "table");
    submenu.css("position", "absolute");
    submenu.css("white-space", "nowrap");
    

    if (layout_direction == "right") {
// 	  left+=menu_item.outerWidth();
      left-=submenu.outerWidth();
      left+= parseInt(menu_item.css("padding-left"));
      left+= parseInt(menu_item.css("width"));
    }

    submenu.offset({top:top, left: left});

}