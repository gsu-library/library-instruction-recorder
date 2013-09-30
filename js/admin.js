//On document load.
jQuery(function($) {
	if($('#classDate').length) {
		$('#classDate').datepicker({
			dateFormat : 'm/d/yy'
		});
	}
});
