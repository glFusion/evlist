/*  Updates database values as checkboxes are checked.
 */
var xmlHttp;
function EVLIST_toggle(ckbox, id, type, component, base_url)
{
  xmlHttp=EV_getXmlHttpObject();
  if (xmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  // value is reversed since we send the oldvalue to ajax
  var oldval = ckbox.checked == true ? 0 : 1;
  var url=base_url + "/ajax.php?action=toggle";

  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&component="+component;
  url=url+"&oldval="+oldval;
  url=url+"&sid="+Math.random();
  xmlHttp.onreadystatechange=EV_stateChanged;
  xmlHttp.open("GET",url,true);
  xmlHttp.send(null);
}

function EV_stateChanged()
{
  var newstate;

  if (xmlHttp.readyState==4 || xmlHttp.readyState=="complete")
  {
    xmlDoc=xmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    component = xmlDoc.getElementsByTagName("component")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        document.getElementById("togenabled"+id).checked=true;
        newval = 1;
    } else {
        document.getElementById("togenabled"+id).checked=false;
        newval = 0;
    }
  }

}

function EV_getXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

