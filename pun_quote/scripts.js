/**
 * Allows users to quote posts without a page reloading
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_quote
 */

var selNode = null;
var IE=0;
var MZ=0;
var OP=0; 

function getCountSubString(pattern, str)
{
	var pos = str.indexOf(pattern);
	for (var count = 0; pos != -1; count++)
		pos = str.indexOf(pattern, pos + pattern.length);
	return count;
}

function getSelectedLinks()
{
	try
	{
		var range, links,
		selection = window.getSelection &&
			window.getSelection() || document.selection,
			dummy = arguments.callee.dummy || 
			(arguments.callee.dummy =
			document.createElement('div'));
		
		if (!selection)
			return;
		if (selection.getRangeAt) 
		{
			var start = selection.anchorNode, end = selection.focusNode;
			
			range = selection.getRangeAt(0);
			var c, s = range.startContainer;
			
			if (s.nodeType === Node.TEXT_NODE)
				range.setStartBefore(s.parentNode);
			
			while (c = dummy.firstChild)
				dummy.removeChild(c);
			dummy.appendChild(range.cloneContents());
		}
		else if (selection.createRange)
		{
			range = selection.createRange();
			dummy.innerHTML = range.htmlText;
		}
		else
			return;
		
		links = dummy.getElementsByTagName('a');
		
		var result = '';
		
		if (links && typeof links.item(0) !== 'undefined')
		{
			for (var i = 0, l = links.length; i < l; i++)
			{
				result += ' ' + links[i].href;
			}
		}
		
		links = dummy.getElementsByTagName('img');
		
		if (links && typeof links.item(0) !== 'undefined')
		{
			for (var i = 0, l = links.length; i < l; i++)
			{
				if (links[i].alt != links[i].src)
					result += ' ' + links[i].alt + ' ';
			}
		}
	}
	catch (err)
	{
		result = undefined;
	}
	
	return result;
}

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
		var resultLinks = '';
		
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
				else if ((testNode.nodeName == 'IMG'))
				{
					if (testNode.alt != testNode.src)
						resultLinks += testNode.alt;
					testNode = testNode.parentNode;
				}
				else if ((testNode.nodeName == 'A'))
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
				else if ((testNode.nodeName == 'IMG'))
				{
					if (testNode.alt != testNode.src)
						resultLinks += testNode.alt;
					testNode = testNode.parentNode;
				}
				else if ((testNode.nodeName == 'A'))
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
				else if ((testNode.nodeName == 'IMG'))
				{
					if (testNode.alt != testNode.src)
						resultLinks += testNode.alt;
					testNode = testNode.parentNode;
				}
				else if ((testNode.nodeName == 'A'))
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
	resultLinks += ' ' + getSelectedLinks();
	
	if (result == undefined)
		result = ' ';
	
	try
	{
		if ((result != undefined) && (result != ''))
		{
			var tmpNode = undefined;
			var tmpResult = '';
			var nFlag = 0;
			var tmpRes = result.toString();
			
			var parentTestNode = testNode.parentNode;
			
			for (i = 0; i < parentTestNode.childElementCount; i++)
			{
				tmpNode = parentTestNode.childNodes[i + 1]; // get element from main DIV
				
				if (tmpNode.className == 'quotebox')
				{
					if (tmpRes.indexOf(tmpNode.lastChild.firstChild.innerHTML) != -1)
					{
						
						if ((tmpNode.firstChild.innerHTML != null) || (tmpNode.firstChild.innerHTML != ''))
						{
							nFlag = 1;
							tmpResult += '[quote=' + tmpNode.firstChild.innerHTML.substring(0, tmpNode.firstChild.innerHTML.length - 7) + ']' + tmpNode.lastChild.firstChild.innerHTML + '[/quote] ';
						}
						else
						{
							nFlag = 1;
							tmpResult += '[quote]' + tmpNode.firstChild.innerHTML + '[/quote] ';
						}
					}
				}
				else if (tmpNode.className == 'codebox')
				{
					if (tmpRes.indexOf(tmpNode.firstChild.firstChild.innerHTML) != -1)
					{
						nFlag = 1;
						tmpResult += '[code]' + tmpNode.firstChild.firstChild.innerHTML + '[/code] ';
					}
				}
				else if (tmpNode.nodeName == 'P')
				{
					if (tmpNode.childElementCount > 0)
					{
						var j = 0;
						while(tmpNode.childNodes[j])
						{
							var secondTmpNode = tmpNode.childNodes[j];
							
							if (secondTmpNode.nodeName == 'STRONG')
							{
								if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
								{
									nFlag = 1;
									tmpResult = tmpResult + '[b]' + secondTmpNode.innerHTML + '[/b] ';
								}
							}
							else if (secondTmpNode.nodeName == 'EM')
							{
								if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
								{
									nFlag = 1;
									tmpResult += '[i]' + secondTmpNode.innerHTML + '[/i] ';
								}
							}
							else if (secondTmpNode.nodeName == 'SPAN')
							{
								if (secondTmpNode.className == 'bbu')
								{
									if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
									{
										nFlag = 1;
										tmpResult += '[u]' + secondTmpNode.innerHTML + '[/u] ';
									}
								}
								else if (secondTmpNode.style[0] == 'color')
								{
									if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
									{
										nFlag = 1;
										tmpResult += '[color=' + secondTmpNode.style.color + ']' + secondTmpNode.innerHTML + '[/color] ';
									}
								}
								else if (secondTmpNode.className == 'postimg')
								{
									if (secondTmpNode.firstChild.nodeName == 'IMG')
									{
										if (tmpRes.indexOf(secondTmpNode.firstChild.src) != -1)
										{
											nFlag = 1;
											tmpResult += '[img]' + tmpRes + '[/img] ';
										}
									}
								}
							}
							else if (secondTmpNode.nodeName == 'A')
							{
								if (secondTmpNode.href.substring(0, 6) == 'mailto')
								{
									if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
									{
										nFlag = 1;
										tmpResult += '[email]' + secondTmpNode.innerHTML + '[/email] ';
									}
								}
								else if (secondTmpNode.innerHTML == '&lt;image&gt;')
								{
									if (ContentCleaning(result.toString()) != '')
									{
										if ((resultLinks.toString().indexOf(secondTmpNode.href) != -1) && (tmpRes.indexOf(secondTmpNode.innerHTML)))
										{
											nFlag = 1;
											tmpResult += '[img]' + secondTmpNode.href + '[/img] ';
										}
									}
								}
								else if (secondTmpNode.href.search(/^([a-zA-Z0-9]+\.+(png|gif|jpeg|jpg|ico))+$/) == -1)
								{
									if (tmpRes.indexOf(secondTmpNode.innerHTML) != -1)
									{
										nFlag = 1;
										tmpResult += '[url]' + secondTmpNode.innerHTML + '[/url] ';
									}
								}
							}
							else if (secondTmpNode.nodeName == 'IMG') // if this is smileys
							{
								if ((resultLinks.indexOf(secondTmpNode.alt) != -1) && (secondTmpNode.alt != secondTmpNode.src))
								{
									nFlag = 1;
									kol1 = getCountSubString(secondTmpNode.alt, resultLinks);
									kol2 = getCountSubString(secondTmpNode.alt, tmpResult);
									
									if (kol1 > kol2)
										tmpResult += ' ' + secondTmpNode.alt + ' ';
								}
							}
							else if (secondTmpNode.nodeName == '#text')
							{
								if (tmpRes.indexOf(secondTmpNode.nodeValue) != -1)
								{
									nFlag = 1;
									tmpResult += secondTmpNode.nodeValue + ' ';
								}
							}
							
							j++;
						}
					}
					else
						if (tmpRes.indexOf(tmpNode.innerHTML) != -1)
						{
							nFlag = 1;
							tmpResult += tmpNode.innerHTML + ' ';
						}
				}
				else if (tmpNode.nodeName == 'UL')
				{
					var secondTmpNode = undefined;
					
					for (j = 0; j < tmpNode.childElementCount; j++)
					{
						secondTmpNode = tmpNode.childNodes[j]; // get a tag "LI"
						
						if (tmpRes.indexOf(secondTmpNode.firstChild.innerHTML) != -1)
						{
							if (nFlag == 0)
								tmpResult += '[list=*] ';
							tmpResult += '[*]' + secondTmpNode.firstChild.innerHTML + '[/*] ';
							nFlag = 1;
							
							var flag = 1;
							
							while(flag == 1)
							{
								j++;
								secondTmpNode = tmpNode.childNodes[j]; // get a tag "LI"
								
								if ((j < (tmpNode.childElementCount)) && (tmpRes.indexOf(secondTmpNode.firstChild.innerHTML) != -1))
								{
									tmpResult += '[*]' + secondTmpNode.firstChild.innerHTML + '[/*] ';
								}
								else
									flag = 0;
							}
							if (nFlag == 1)
								tmpResult += '[/list] ';
							break;
						}
					}
				}
				else if (tmpNode.nodeName == '#text')
				{
					nFlag = 1;
					tmpResult += tmpNode.innerHTML + ' ';
				}
				
				if (nFlag == 1)
				{
					result = tmpResult;
				}
				nFlag = 0;
			}
		}
	}
	catch(err)
	{
		result = undefined; // there is text with BBcode
	}
	return result; // there is text without BBcode
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
		var clean_selected_text = ContentCleaning(ParseSmiles(selected_text.toString()));
		
		if (post_content.indexOf(clean_selected_text) != -1)
		{
			var quote = '[quote=' + pun_quote_authors[qid_param] + ']' + ParseSmiles(clean_selected_text) + '[/quote]';
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

function ParseSmiles(string)
{
	var search_arr = new Array(/\bsmile\s/g, /\bbig_smile\s/g, /\bneutral\s/g, /\bsad\s/g, /\byikes\s/g, /\bwink\s/g, /\bhmm\s/g, /\btongue\s/g, /\blol\s/g, /\bmad\s/g, /\broll\s/g, /\bcool\s/g);
	var replace_arr = new Array(' :) ', ' :D ', ' :| ', ' :( ', ' :o ', ' ;) ', ' :/ ', ' :P ', ' :lol: ', ' :mad: ', ' :rolleyes: ', ' :cool: ');
	for (var replace_num = 0; replace_num < search_arr.length; replace_num++)
		string = string.replace(search_arr[replace_num], replace_arr[replace_num]);

	return string;
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