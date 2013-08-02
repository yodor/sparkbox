
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
	$(this).mouseenter(function(){
		instance.menuEnter(this);
	});

	$(this).mouseleave(function(){
		instance.menuLeave(this);
	});

	$(this).click(function(event){
		
		var link = $(this).find("A").first();
		var href = link.attr("href");
		window.location.href=href;
		
	});
	
  });

  $(this.cls).first().find(" .SubmenuRenderer").each(function(index) {
       $(this).click(function(event){

	  return false;
	
      });
  });
  
  $(this.cls).first().find(" .SubmenuItemRenderer").each(function(index) {
	$(this).mouseenter(function(event){
		instance.submenuEnter(this, event);
	});
	
	$(this).click(function(event){
		var link = $(this).find("A").first();
		var href = link.attr("href");
		window.location.href=href;
		return false;
	});


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
//   console.log("menuLeave");
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