// Find out if IE runs in quirks mode
var documentElement = (
             typeof document.compatMode != "undefined" && 
             document.compatMode        != "BackCompat"
            )? "documentElement" : "body";

// Register event
function init_getSelectedText() {
    document.onmouseup = getSelectedText;
    document.onmousedown = resetClassAttribute;
}


function getSelectedText(e) {	

	var txt = '';
	if (window.getSelection) {
		txt = window.getSelection();
	} else if (document.getSelection) {
		txt = document.getSelection();
	} else if (document.selection) {
		txt = document.selection.createRange().text;
	} else return;

	// Get mouse position
	xPos = 0;
	yPos = 0;
    // Position where the mouse event fired
    var xPos =  e? e.pageX : window.event.x;
	var yPos =  e? e.pageY : window.event.y;
	// For IE: add scroll position
	if (document.all && !document.captureEvents) {
	    xPos += document[documentElement].scrollLeft;
	    yPos += document[documentElement].scrollTop;
    }
 
	var panel = document.getElementById('tx_contagged_panel');
	if (txt!='' && panel ) {
		if (panel.getAttribute('class')=='') {
			panel.style.visibility = 'visible';
			panel.style.top = (yPos-20)+'px';
			panel.style.left = xPos+'px';
			panel.setAttribute('class','fixed');
			var form = panel.getElementsByTagName('FORM')[0];
			// alert(form);
			var link = form.getElementsByTagName('A')[0];
			onclick = link.getAttribute('onclick');
			parts = onclick.split('&noView=0');
			// alert(parts[1]);
			newOnclick = parts[0]+'&noView=0&defVals[tx_contagged_terms][term_main]='+txt+'&'+parts[1];
			link.setAttribute('onclick',newOnclick);
			// childs = form[0].getElementById('tx_contagged_defVal');
			// childs = form[0];
			// if (false) {
			// 	childs[0].setAttribute('value',txt);				
			// } else {
			// 	var hiddenField = document.createElement('input');
			// 	hiddenField.setAttribute('type','hidden');
			// 	hiddenField.setAttribute('name','TSFE_EDIT[data][tx_contagged_terms][NEW][term_main]');
			// 	hiddenField.setAttribute('value',txt);
			// 	hiddenField.setAttribute('id','tx_contagged_defVal');
			// 	form[0].insertBefore(hiddenField,form[0].firstChild);				
			// }
		}
	// '<input type="hidden" name="TSFE_EDIT[data][tx_contagged_terms][NEW][term_main]" value="'+txt+'" />');
	} else {
		panel.style.visibility = 'hidden';
	}
}

function resetClassAttribute(e) {
	var panel = document.getElementById('tx_contagged_panel');
	if (panel.getAttribute('class')=='fixed') {
		panel.setAttribute('class','second');
	} else {
		panel.setAttribute('class','');
	}
}