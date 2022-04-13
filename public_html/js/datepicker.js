$(document).ready(function(){
	$('[data-ev-datepicker]').each (function(i, obj) {
		var id = $(this).attr('id');
		ev_datetimepicker_datepicker(id);
	});

	$('[data-ev-timepicker]').each(function(i, obj) {
		var id = $(this).attr('id');
		ev_datetimepicker_timepicker(id);
	});
});
function ev_datetimepicker_datepicker( selector ) {
	var currentDT = $("#"+selector).val();
	$('#'+selector).datetimepicker({
		lazyInit: true,
		setValue: currentDT,
		format:'Y-m-d',
		timepicker: false,
	});
}
function ev_datetimepicker_timepicker( selector ) {
	var currentDT = $("#"+selector).val();
	$('#'+selector).datetimepicker({
		lazyInit: true,
		setValue: currentDT,
		format:'H:i',
		datepicker: false,
		step: 15,
	});
}
