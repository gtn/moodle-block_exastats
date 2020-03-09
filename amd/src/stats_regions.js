
define(['jquery'], function($) {

	// show/hide bezirk by selected region
	function updateBezirkSelectBox() {
		// at first - hide all bezirke selectboxes
		$('.bezirkSelectBox').closest('.form-group').hide();
		var regionSelectedCount = $("#regionselect option:selected").length;
		if (regionSelectedCount == 1) {
			var selectedRegion = $("#regionselect option:selected").first().val();
			//console.log(selectedRegion);
			if ($('#bezirkselect'+selectedRegion).length) {
				// display bezirk selectbox
				$('#bezirkselect'+selectedRegion).closest('.form-group').show();
			}
		}
		// unselect all hidden options
		$('.bezirkSelectBox').find('option:hidden').prop("selected", false);
	}

	// MAIN code of MODULE
	return {
		initialise: function() {
			//console.log('exastats AMD loaded');
			updateBezirkSelectBox();
			$('#regionselect').on('change', updateBezirkSelectBox);
			// clear filters button
			$('#clear_filter').on('click', function() {
				$(this).closest('form').find('input[type="text"]').val('');
				$(this).closest('form').find('select option').removeAttr('selected');
				//$(this).closest('form').trigger('reset');
				updateBezirkSelectBox();
			});
		},
	};

});


