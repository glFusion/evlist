$(document).ready(function(){
	$('[data-ev-datepicker]').each (function(i, obj) {
		var id = $(this).attr('id');
		ev_datetimepicker_datepicker(id);
	});

	$('[data-ev-timepicker]').each(function(i, obj) {
		var id = $(this).attr('id');
		ev_datetimepicker_timepicker(id);
	});
	$('[data-ev-calswitcher]').each (function(i, obj) {
		ev_calswitcher($(this).attr('id'));
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

function ev_calswitcher(selector) {
	var sel_id = '#' + selector;
	$(sel_id).datetimepicker({
		lazyInit: true,
		value: $(sel_id + "_val").val(),
		format:'Y-m-d',
		timepicker: false,
		altField: sel_id + '_alt',
		altFormat: 'yy-mm-dd',
		onSelectDate( dp,$input ) {
			var month = parseInt($input.val().substr(5,2),10);
			var year  = parseInt($input.val().substr(0,4),10);
			var day = parseInt($input.val().substr(8,2),10);
			window.location.href = glfusionSiteUrl + "/evlist/index.php?year=" + year + "&month=" + month + "&day=" + day;
		},
	});
}

