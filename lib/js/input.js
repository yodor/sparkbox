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