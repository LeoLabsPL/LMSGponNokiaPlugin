function isObject(a) 
{
	return (typeof a == 'object' && !!a) || isFunction(a);
}
function isFunction(a) 
{
	return typeof a == 'function';
}

function ShowGponPorts(countPorts,defaultvalue)
{
	var tablePorts=new Array();
	var textTemp='';
	var lp;
	if(countPorts>0)
	{
		for(i=0;i<countPorts;i++)
		{
			lp = i + 1;
			textTemp = textTemp+'<tr><td align="right">' + lp + '</td>' +
				'<td align="right"><input type="hidden" id="countlinkporthidden[' + lp + ']" value="0"><input type="hidden" id="gponoltportshidden[' + lp + ']" value="0"><span id="countlinkport[' + lp + ']"></span></td>' +
				'<td><INPUT TYPE="TEXT" NAME="gponoltports[' + lp + ']" ID="gponoltports[' + lp + ']" VALUE="' + defaultvalue + '"  onkeyup="TestGponPorts(this,' + lp + ')" size="5" title="Podaj liczbę ONU" /></td>' +
				'<td align="right"><input type="hidden" id="descporthidden[' + lp + ']" value=""><input type="hidden" id="gponoltdescportshidden[' + lp + ']" value=""><span id="descport[' + lp + ']">-</span></td></tr>';
		}
		document.getElementById('GponPorts').innerHTML = '<table cellpadding="2" border="1"><tr><td><b>Port</b></td>' +
			'<td><strong>Occupied by the ONU</strong></td>' +
			'<td><strong>Max. ONU per port</strong></td>' +
			'<td><strong>Description</strong></td></tr>' + textTemp + '</table>';
	}
	else
	{
		document.getElementById('GponPorts').innerHTML='';
	}
}
function TestGponPorts(obiekt,lp)
{
	if(isObject(obiekt))
	{
		ZamienNaLiczbe(obiekt);
		var gponoltports=document.getElementById('gponoltportshidden['+lp+']').value;
		var countlink=document.getElementById('countlinkporthidden['+lp+']').value;
		if(obiekt.value<countlink)
		{
			alert('Nie można zmniejszyć liczby max. ONU. Port zajęty jest już przez '+countlink+' ONU.');
			obiekt.value=gponoltports;
		}
	}
}

function ZamienNaLiczbe(obiekt)
{
	if(isObject(obiekt))
	{
		obiekt.value=intval(obiekt.value);
	}
}
function SetObjectValue(idobiektu,wartosc)
{
	var obiekt=document.getElementById(idobiektu);
	if(isObject(obiekt))
	{
		obiekt.value=wartosc;
	}
}
function SetObjectinnerHTML(idobiektu,wartosc)
{
	var obiekt=document.getElementById(idobiektu);
	if(isObject(obiekt))
	{
		obiekt.innerHTML=wartosc;
	}
}
function MyConfirm(tekst)
{
	if(confirm(tekst))
	{
		return true;
	}
	else
	{
		return false;
	}
}
function intval (mixed_var, base) {
    // Get the integer value of a variable using the optional base for the conversion  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/intval
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: stensi
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   input by: Matteo
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Rafał Kukawski (http://kukawski.pl)
    // *     example 1: intval('Kevin van Zonneveld');
    // *     returns 1: 0
    // *     example 2: intval(4.2);
    // *     returns 2: 4
    // *     example 3: intval(42, 8);
    // *     returns 3: 42
    // *     example 4: intval('09');
    // *     returns 4: 9
    // *     example 5: intval('1e', 16);
    // *     returns 5: 30
    var tmp;
 
    var type = typeof(mixed_var);
 
    if (type === 'boolean') {
        return +mixed_var;
    } else if (type === 'string') {
        tmp = parseInt(mixed_var, base || 10);
        return (isNaN(tmp) || !isFinite(tmp)) ? '' : tmp;
    } else if (type === 'number' && isFinite(mixed_var)) {
        return mixed_var | 0;
    } else {
        return '';
    }
}