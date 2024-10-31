jQuery(document).ready(function ($) {

	var spinner = $('#rmbp-spinner');

	$(document).on('click', '#wpgo-reset-meta-boxes', function (event) {

		spinner.addClass('is-active');
		$('#rmbp-submit, #wpgo-reset-meta-boxes').attr("disabled", true);

		// get selected CPT checkboxes
		var cpt_arr = [];
		var chk_sel = document.querySelectorAll('#cpt-chk-tr input[type="checkbox"]:checked').forEach(function(el) { cpt_arr.push(el.dataset.cpt); });

		data = {
			action             : 'rmbp_reset_mb',
			rmbp_ajax_nonce: rmbp_vars.rmbp_nonce,
			chk_boxes: cpt_arr
		};

		$.post(ajaxurl, data, function (response) {

			// remove previous admin notice
			$('#setting-error-settings_updated').remove();

			if(!response.includes("no-chk-boxes")) {
				$('#rmbp-spinner').after('<div style="margin-top: 20px;" id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>' + response + '</strong></p><button type="button" id="rmbp-dismiss" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
			} else {
				$('#rmbp-spinner').after('<div style="margin-top: 20px;" id="setting-error-settings_updated" class="error settings-error notice is-dismissible"><p><strong>No check boxes selected!</strong></p><button type="button" id="rmbp-dismiss" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
			}

			$(document).on('click', '#rmbp-dismiss', function (event) {
				$('#setting-error-settings_updated').remove();
			});

			//$('#rmbp-response').html(response);

			spinner.removeClass('is-active');
			$('#rmbp-submit, #wpgo-reset-meta-boxes').attr("disabled", false);
		});

		return false; // make sure the normal submit behaviour is suppressed
	});
});