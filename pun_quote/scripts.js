/**
 * Allows users to quote posts without a page reloading
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_quote
 */

var selNode = null;

function getCaretPos()
{
	var obj = document.getElementById('fld1');
	if (document.selection)
	{ // IE
		obj.focus();
		var sel = document.selection.createRange();
		sel.moveStart ('character', -obj.value.length);

		return sel.text.length;
	}
	else if (obj.selectionStart !== false)
		return obj.selectionStart; // Gecko
	else
		return 0;
}

function setCaretPos(pos)
{
	var obj = document.getElementById('fld1');
	if (obj.setSelectionRange)
	{
		obj.focus();
		obj.setSelectionRange(pos, pos);
	}
	else if (obj.createTextRange)
	{
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}
}

function getSelectedText()
{
	try
	{
		var result = undefined;
		if (document.selection) //IE & Opera
		{
			selNode = document.selection.createRange().parentElement();
			
			var testNode = selNode;
			var flag = 1;
			
			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'LI'))
					testNode = testNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'UL'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'EM'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'STRONG'))
					testNode = testNode.parentNode;	
				else if ((testNode.nodeName == 'SPAN'))
					testNode = testNode.parentNode;
				else
					flag = 0;
			}

			if ((testNode.parentNode.className == 'entry-content') && (testNode.className != 'sig-content'))
				result = document.selection.createRange().text;
		}
		else if (document.getSelection) //FF
		{
			selNode = window.getSelection().anchorNode.parentNode;

			var testNode = selNode;
			var flag = 1;

			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'LI'))
					testNode = testNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'UL'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'EM'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'STRONG'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'SPAN'))
					testNode = testNode.parentNode;
				else
					flag = 0;
			}
			
			if ((testNode.parentNode.className == 'entry-content') && (testNode.className != 'sig-content'))
				result = document.getSelection();
		}
		else if (window.getSelection) //Google Chrome & Safari
		{
			selNode = window.getSelection().anchorNode.parentNode;
			
			var testNode = selNode;
			var flag = 1;
			
			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'LI'))
					testNode = testNode.parentNode;
				else if ((testNode.parentNode.nodeName == 'UL'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'EM'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'STRONG'))
					testNode = testNode.parentNode;
				else if ((testNode.nodeName == 'SPAN'))
					testNode = testNode.parentNode;
				else
					flag = 0;
			}

			if ((testNode.parentNode.className == 'entry-content') && (testNode.className != 'sig-content'))
				result = window.getSelection();
		}
		else
			return result;
	}
	catch(err)
	{
		result = undefined;
	}

	return result;
}

function QuickQuote(qid_param)
{
	var selected_text = getSelectedText();
	var quick_post_value = document.getElementsByName('req_message');
	var cur_pos = getCaretPos();
	var text = quick_post_value[0].value;
	var text_below = text.substring(0, cur_pos);
	var text_above = text.substring(cur_pos, text.length);

	if (selected_text == undefined || selected_text == '')
	{
		var quote = '[quote=' + pun_quote_authors[qid_param] + ']' + ParseMessage(pun_quote_posts[qid_param]) + '[/quote]';
		quick_post_value[0].value = text_below + quote + text_above;
	}
	else
	{
		var post_content = ContentCleaning(ParseMessage(pun_quote_posts[qid_param]));
		var clean_selected_text = ContentCleaning(selected_text.toString());
		if (post_content.indexOf(clean_selected_text) != -1)
		{
			var quote = '[quote=' + pun_quote_authors[qid_param] + ']' + selected_text + '[/quote]';
			quick_post_value[0].value = text_below + quote + text_above;
		}
		else
		{
			var quote = '[quote=' + pun_quote_authors[qid_param] + ']' + ParseMessage(pun_quote_posts[qid_param]) + '[/quote]';
			quick_post_value[0].value = text_below + quote + text_above;
		}
	}
	setCaretPos(text_below.length + quote.length);
}

function Reply(qid_param, quote_link)
{
	var selected_text = getSelectedText().toString();
	if (selected_text == undefined || selected_text == '')
		return;

	var post_content = ContentCleaning(ParseMessage(pun_quote_posts[qid_param]));
	var clean_selected_text = ContentCleaning(selected_text);

	//Get Quote form
	var pun_quote_form = document.getElementById('pun_quote_form');
	if (post_content.indexOf(clean_selected_text) != -1)
	{	
		//Change quote message
		document.getElementById('post_msg').value = selected_text;
		//Change URL
		pun_quote_form.action = document.getElementById('pun_quote_url').value.replace('$2', qid_param.toString());
		pun_quote_form.submit();
	}
	else
		location = quote_link.href;	
}

function ParseMessage(string)
{
	var search_arr = new Array(/&amp;/g, /&quot;/g, /&#039;/g, /&lt;/g, /&gt;/g);
	var replace_arr = new Array('&', '"', '\'', '<', '>');
	for (var replace_num = 0; replace_num < search_arr.length; replace_num++)
		string = string.replace(search_arr[replace_num], replace_arr[replace_num]);

	return string;
}

function ContentCleaning(string)
{
	//\n\r, \n, \r 
	string = string.replace(/(\n\r|\n|\r){1,}/gi,' ');
	//trim message
	string = string.replace(/^\s+|\s+$/g, '');
	//more than 1 space
	string = string.replace(/\s{1,}/gi, ' ');
	return string;
}