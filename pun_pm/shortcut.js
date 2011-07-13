/**
 * Provides keybord shortcuts for send form in pun_pm
 *
 * @copyright (C) 2008-2009 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_pm
 */

(function(global){
	// Setup variables
	var ua = navigator.userAgent.toLowerCase(),
		isIE = (ua.indexOf("msie") != -1 && ua.indexOf("opera") == -1),
		isSafari = ua.indexOf("safari") != -1,
		isGecko = (ua.indexOf("gecko") != -1 && !isSafari);


	function add_handler(event, handler) {
		if (document.addEventListener)
			document.addEventListener(event, handler, false);
		else if (document.attachEvent)
			document.attachEvent('on' + event, handler);
		else
			return false;

		return true;
	}


	function key_handler(e) {
		e = e || window.event;
		var key = e.keyCode || e.which;

		if (e.ctrlKey && (isGecko && key == 115 || !isGecko && key == 83)) {
			if (e.preventDefault)
				e.preventDefault();
			e.returnValue = false;

			document.forms.pun_pm_sendform.send_action.value = 'draft';
			document.forms.pun_pm_sendform.submit();

			return false;
		}

		if (e.ctrlKey && (key == 13 || key == 10)) {
			if (e.preventDefault)
				e.preventDefault();
			e.returnValue = false;

			document.forms.pun_pm_sendform.send_action.value = 'send';
			document.forms.pun_pm_sendform.submit();

			return false;
		}
	}


	function pun_pm_onload() {
		var result = isIE || isSafari ? add_handler("keydown", key_handler) : add_handler("keypress", key_handler);

		if (result) {
			setTimeout("document.forms.pun_pm_sendform.pm_send.title = 'Ctrl + Enter'", 500);
			setTimeout("document.forms.pun_pm_sendform.pm_draft.title = 'Ctrl + S'", 500);
		}
	}


	// Run on page load
	PUNBB.common.addDOMReadyEvent(pun_pm_onload);
})(window);
