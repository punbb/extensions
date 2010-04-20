/**
 * Provides inserting of BBcodes and smilies
 *
 * @copyright (C) 2008-2010 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_bbcode
 */

function insert_text(open, close)
{
	msgfield = (document.all) ? document.all.req_message : ((document.getElementById('afocus') != null) ? (document.getElementById('afocus').req_message) : (document.getElementsByName('req_message')[0]));

	// IE support
	if (document.selection && document.selection.createRange)
	{
		msgfield.focus();
		sel = document.selection.createRange();
		sel.text = open + sel.text + close;
		msgfield.focus();
	}

	// Moz support
	else if (msgfield.selectionStart || msgfield.selectionStart == '0')
	{
		var startPos = msgfield.selectionStart;
		var endPos = msgfield.selectionEnd;
		var old_top = msgfield.scrollTop;
		msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos) + close + msgfield.value.substring(endPos, msgfield.value.length);
		msgfield.selectionStart = msgfield.selectionEnd = endPos + open.length + close.length;
		msgfield.scrollTop = old_top;
		msgfield.focus();
	}

	// Fallback support for other browsers
	else
	{
		msgfield.value += open + close;
		msgfield.focus();
	}
}
