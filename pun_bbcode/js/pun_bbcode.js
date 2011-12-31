/**
 * Provides inserting of BBcodes and smilies
 *
 * @copyright (C) 2008-2011 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_bbcode
 */
/*jslint browser: true, maxerr: 50, indent: 4 */
/*global PUNBB: true */

PUNBB.pun_bbcode = (function () {
	'use strict';

	return {

		//
		init: function () {

			return true;
		},

		//
		insert_text: function (open, close) {
			var sel, sel_length, msgfield = (document.all) ? document.all.req_message : ((document.getElementById('afocus') !== null) ? (document.getElementById('afocus').req_message) : (document.getElementsByName('req_message')[0]));

			if (!msgfield) {
				return false;
			}

			// IE support
			if (document.selection && document.selection.createRange) {
				msgfield.focus();
				sel = document.selection.createRange();
				sel.text = open + sel.text + close;
				msgfield.focus();
			} else if (msgfield.selectionStart || msgfield.selectionStart === 0) {
				// Moz support
				var startPos = msgfield.selectionStart,
					endPos = msgfield.selectionEnd,
					old_top = msgfield.scrollTop;

				msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);

				// For tag with attr set cursor after '='
				if (open.charAt(open.length - 2) === '=') {
					msgfield.selectionStart = (startPos + open.length - 1)
				} else if (startPos === endPos) {
					msgfield.selectionStart = endPos + open.length;
				} else {
					msgfield.selectionStart = endPos + open.length + close.length;
				}

				msgfield.selectionEnd = msgfield.selectionStart;
				msgfield.scrollTop = old_top;
				msgfield.focus();
			} else {
				// Fallback support for other browsers
				msgfield.value += open + close;
				msgfield.focus();
			}
		}
	};
}());

// One onload handler
PUNBB.common.addDOMReadyEvent(PUNBB.pun_bbcode.init);
