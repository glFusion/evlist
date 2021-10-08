/*  Updates database values as checkboxes are checked.
 */
var EVLIST_toggle = function(cbox, id, type, component, base_url) {
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
                $.UIkit.notify("<i class='uk-icon-check'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                alert(result.statusMessage);
            }
        }
    });
    return false;
}

/*  Updates database values as selections are changed.
 */
var EVLIST_updateStatus = function(sel, type, id, oldval, base_url) {
    var dataS = {
        "action" : "setStatus",
        "id": id,
        "type": type,
        "newval": sel.value,
		"oldval": oldval
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: base_url + "/ajax.php",
        data: data,
        success: function(result) {
            try {
                $.UIkit.notify("<i class='uk-icon-check'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                alert(result.statusMessage);
            }
        }
    });
    return false;
}


