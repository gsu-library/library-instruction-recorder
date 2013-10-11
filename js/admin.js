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
	$('.removeLink').each(function() {
		$(this).click(function(e) {
			e.preventDefault();
		});
	});

	//Stops the details links on the upcoming classes page from firing.
	$('.detailsLink').each(function() {
		$(this).click(function(e) {
			e.preventDefault();
		});
	});
});


/*
	Functions
*/
var $j = jQuery.noConflict();


function removeClass(url) {
	var check = confirm("Are you sure you want to remove this class?");

	if(check)
		window.location.href = url;
}


function showDetails(id) {
	var $element = $j('<table></table>').attr({cellspacing: 0, cellpadding: 0});
	
	$j('.'+id+' > td').each(function() {
		if($j(this).attr('name')) {
			if($j(this).attr('name') == 'skip') return true;
			field = $j(this).attr('name').replace(/-/g, '/').replace(/_/g, ' ');
		}
		else
			field = '';
		
		$j($element).append('<tr><td>'+field+'</td><td>'+$j(this).html()+'</td></tr>');
	});
	
	$j($element).find('tr:last').attr('class', 'last');
	$element = $j('<div></div>').attr('id', 'LIR-popup').append($element);
	
	$j($element).dialog({
		title: 'Details',
		width: 360
	});
}
