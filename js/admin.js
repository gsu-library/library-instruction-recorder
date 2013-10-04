/*
	On document load.
*/
jQuery(function($) {
	if($('#classDate').length) {
		$('#classDate').datepicker({
			dateFormat : 'm/d/yy'
		});
	}
	
	//Stops the delete links on the upcoming classes page from firing.
	$('.removeLink').each(function(){
		$(this).click(function(e) {
			e.preventDefault();
		});
	});
});


/*
	Functions
*/
//If this is needed.
//var $j = jQuery.noConflict();

function removeClass(url) {
	var check = confirm("Are you sure you want to remove this class?");

	if(check)
		window.location.href = url;
}
