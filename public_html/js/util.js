/*
 * Utility javascript functions to abstrace UI elements from the
 * underlying framework. 
 */
var Evlist = (function() {
	return {
		// Display a notification popup for a short time.
		notify: function(message, status='', timeout=1500) {
			if (status == 'success') {
				var icon = "<i class='uk-icon uk-icon-check'></i>&nbsp;";
			} else if (status == 'warning') {
				var icon = '<i class="uk-icon uk-icon-exclamation-triangle"></i>&nbsp';
			} else {
				var icon = '';
			}
			if (typeof UIkit.notify === 'function') {
				// uikit v2 theme
	            UIkit.notify(icon + message, {timeout: timeout});
			} else if (typeof UIkit.notification === 'function') {
		        // uikit v3 theme
				UIkit.notification({
		            message: icon + message,
				    timeout: timeout,
		            status: status,
				});
		    } else {
				alert(message);
			}
		},
		toggle: function(cbox, id, type, component, base_url) {
			oldval = cbox.checked ? 0 : 1;
		    var dataS = {
				"action" : "toggle",
		        "id": id,
				"type": type,
		        "oldval": oldval,
				"component": component,
		    };
		    data = $.param(dataS);
		    $.ajax({
				type: "POST",
		        dataType: "json",
				url: base_url + "/ajax.php",
		        data: data,
				success: function(result) {
		            cbox.checked = result.newval == 1 ? true : false;
				    try {
						Evlist.notify(result.statusMessage, 'success');
		            } catch(err) {
				        alert(result.statusMessage);
		            }
				}
		    });
		    return false;
		}
	};
})();

