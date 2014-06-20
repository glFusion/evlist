/**
*   Calendar selection javascript.
*   Allows the user to select or deselect calendars and have the 
*   corresponding events displayed or removed from the calendar view
*
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

function clickCalendarButton() {
    var action = document.getElementById('calendar_dialog');
    action.style.display="block";
    action.style.visibility="visible";
    return;
}

function closedialog() {
    var action = document.getElementById('calendar_dialog');
    action.style.display="none";
    action.style.visibility="hidden";
    return;
}

function SelectCal(cal_chk)
{
    var cls = "show_" + cal_chk.id;

    var el = getElementsByClass(document,cls,'*');
    if (cal_chk.checked == 1) {
        //var newvis = 'visible';
        var newvis = 'block';
    } else {
        //var newvis = 'hidden';
        var newvis = 'none';
    }
    for (i = 0; i < el.length; i++) {
        //el[i].style.visibility = newvis;
        el[i].style.display = newvis;
    }
}

function getElementsByClass(node,searchClass,tag)
{
    var classElements = new Array();
    var els = node.getElementsByTagName(tag); // use "*" for all elements
    var elsLen = els.length;
    var pattern = new RegExp("\\b"+searchClass+"\\b");
    for (i = 0, j = 0; i < elsLen; i++) {
        if ( pattern.test(els[i].className) ) {
            classElements[j] = els[i];
            j++;
        }
    }
    return classElements;
}

function getCals(cls) {
    var el = getElementsByClass(document,cls,'*');
}

