jQuery(function($)
{
	function run_ajax(obj)
	{
		obj.button.addClass('is_disabled');
		obj.selector.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.ajax(
		{
			url: script_fortnox.ajax_url,
			type: 'post',
			dataType: 'json',
			data: obj.data,
			success: function(data)
			{
				obj.button.removeClass('is_disabled');

				obj.selector.html(data.html);
			}
		});

		return false;
	}

	$(document).on('click', "button[name='btnFortnoxRun']:not(.is_disabled)", function(e)
	{
		var dom_obj = $(e.currentTarget),
			dom_obj_action = $(e.currentTarget).parent("div").siblings("p").attr('id');

		run_ajax(
		{
			'button': dom_obj,
			'data': {
				'action': dom_obj_action,
			},
			'selector': $("#" + dom_obj_action)
		});
	});
});