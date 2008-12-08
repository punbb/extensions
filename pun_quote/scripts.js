/**
 * Allows users to quote posts without a page reloading
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_quote
 */

document.onmouseup = SetSelected;
var selNode;

function turnOnLinks()
{
	var p = document.getElementsByTagName('p');

	var tmp_k = 0;

	for (var k=0; k < p.length; k++ )
	{
		if (p[k].className.match(/post-actions/))
		{
			var span = p[k].getElementsByTagName('span');
	
			if (tmp_k == 0)
			{
				span[5].style.display = "";
				tmp_k = 1;
				var a = span[7].getElementsByTagName('a');

				if (a.length == 0)
				{
					tmp_k = 1;
					k = 0;
				}
				else
					a[0].href = "javascript: QuickQuote()";
			}
			else
			{
				span[6].style.display = "";
				var a = span[8].getElementsByTagName('a');
				a[0].href = "javascript: QuickQuote()";
			}
		}
	}
}

function getSelectedText()
{
	try
	{
		var result = undefined;
		
		if (document.selection)
		{
			selNode = document.selection.createRange().parentElement();
			
			var testNode = selNode;
			var flag = 1;
			
			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
						testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
					testNode = testNode.parentNode;						
				else
					flag = 0;
			}
			
			if ((testNode.parentNode.className == 'entry-content') && (testNode.className != 'sig-content'))
				result = document.selection.createRange().text;
		}
		else if (document.getSelection)
		{
			selNode = window.getSelection().anchorNode.parentNode;
			
			var testNode = selNode;
			var flag = 1;
			
			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
						testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
					testNode = testNode.parentNode;
				else
					flag = 0;
					
	
			}
			
			if ((testNode.parentNode.className == 'entry-content') && (testNode.className != 'sig-content'))
				result = document.getSelection();
		}
		else if (window.getSelection)
		{
			selNode = window.getSelection().anchorNode.parentNode;
			
			var testNode = selNode;
			var flag = 1;
			
			while(flag == 1)
			{
				if ((testNode.parentNode.nodeName == 'BLOCKQUOTE'))
					testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CODE'))
						testNode = testNode.parentNode.parentNode;
				else if ((testNode.nodeName == 'CITE'))
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

function Reply(tid_param, qid_param, d)
{	
	var selected_text = getSelectedText();
	var element;
	var reply_text = "";
	var pun_quote_whole_citing = 0;

	if ((selected_text != undefined) && (selected_text != ''))
	{
		while(selNode.className.indexOf('postbody') == -1)
			selNode = selNode.parentNode;
			
		selNode = selNode.parentNode.getElementsByTagName('a');
		
		for (i = 0; i < selNode.length; i++)
		{
			if (selNode[i].innerHTML.indexOf('Reply') != -1)
			{	
				if (d == selNode[i])
				{
					var reply_url = document.getElementById("pun_quote_url");
					reply_url = reply_url.value;
					var replace_url;
					var pun_quote_post = d.parentNode.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('div');
					var flag = 1;
					var i = 0;
				
					while(pun_quote_post[i].className != 'entry-content')
						i++;
					
					var author = pun_quote_post[i].parentNode.parentNode.parentNode.getElementsByTagName('a');
					author = author[0].innerHTML;
					
					if((selected_text.type=='Range') || (selected_text.type=='Caret'))
						selected_text=selected_text.toString();
			
					selected_text = RemoveSymbols(selected_text);
					reply_text +=(window.selected_text_first == '') ? window.selected_text_second + '\n': window.selected_text_first + '\n';
					
					element = document.getElementById('post_msg');
					element.value = reply_text;
					var form = document.getElementById('qq');
					var reply_url = document.getElementById('pun_quote_url').value;
					replace_url = reply_url.replace('$2',qid_param.toString());
					form.action = replace_url.toString();
					form.submit();
					break;
				}
				else
					pun_quote_whole_citing = 1;
			}
		}
	}
	else
		pun_quote_whole_citing = 1;
	
	if (pun_quote_whole_citing == 1)
	{
		var pun_quote_post = d.parentNode.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('div');
		var flag = 1;
		var i = 0;
	
		while(flag == 1)
		{
			if (pun_quote_post[i].className == 'entry-content')
				break;
				
			i++;
		}
		
		var author = pun_quote_post[i].parentNode.parentNode.parentNode.getElementsByTagName('a');
		author = author[0].innerHTML;
		
		var paste_text = pun_quote_posts[qid_param];
		paste_text = paste_text.replace(/<\/br>/ig,'\n');
		element = document.getElementById('post_msg');
		element.value = paste_text;
		var form = document.getElementById('qq');
		var reply_url = document.getElementById('pun_quote_url').value;
		replace_url = reply_url.replace('$2',qid_param.toString());
		form.action = replace_url.toString();
		form.submit();
	}
	
	return false;
}

function QuickQuote(tid_param, qid_param, d)
{
	var selected_text = getSelectedText();
	var element;
	var pun_quote_whole_citing = 0;

	if ((selected_text != undefined) && (selected_text != ''))
	{
		while(selNode.className.indexOf('postbody') == -1)
			selNode = selNode.parentNode;
			
		selNode = selNode.parentNode.getElementsByTagName('a');
		
		for (i = 0; i < selNode.length; i++)
		{
			if (selNode[i].innerHTML.indexOf('Quick quote') != -1)
			{
				if (d == selNode[i])
				{
					var pun_quote_post = d.parentNode.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('div');
					var flag = 1;
					var i = 0;
				
					while(pun_quote_post[i].className != 'entry-content')
						i++;
					
					var author = pun_quote_post[i].parentNode.parentNode.parentNode.getElementsByTagName('a');
					author = author[0].innerHTML;
					
					if((selected_text.type=='Range') || (selected_text.type=='Caret'))
						selected_text=selected_text.toString();
	
					selected_text = RemoveSymbols(selected_text);
					element = document.getElementById('fld1');
					element.value +=(window.selected_text_first == '')?'[quote='+author+']'+window.selected_text_second+'[/quote]'+'\n':'[quote='+author+']'+window.selected_text_first+'[/quote]'+'\n';					
				}
				else
					pun_quote_whole_citing = 1;
					
				break;
			}
		}
	}
	else
		pun_quote_whole_citing = 1;
	
	if (pun_quote_whole_citing == 1)
	{
		var pun_quote_post = d.parentNode.parentNode.parentNode.parentNode.parentNode.getElementsByTagName('div');
		var flag = 1;
		var i = 0;
	
		while(flag == 1)
		{
			if (pun_quote_post[i].className == 'entry-content')
				break;
				
			i++;
		}
		
		var author = pun_quote_post[i].parentNode.parentNode.parentNode.getElementsByTagName('a');
		author = author[0].innerHTML;
		
		element = document.getElementById('fld1');
		var paste_text = pun_quote_posts[qid_param];
		paste_text = paste_text.replace(/<\/br>/ig,'\n');
		element.value += '[quote='+author+']' + paste_text + '[/quote]'+'\n';
		return false;
	}
	
	return false;
}

function RemoveSymbols(string)
{
	string = string.replace(/\r*/gi,'');
	string = string.replace(/\n*/gi,'');
	string = string.replace(/\s*/gi,'');
	string = string.replace(/\u00A0/g,' ');
	string = string.replace(/&nbsp;/g,' ');
	string = string.replace(/&lt;/g,'<');
	string = string.replace(/&gt;/g,'>');
	string = string.replace(/<BR>/ig,'');
	return string;
}

function SetSelected()
{
	switch(window.selected_text_pointer)
	{
		case 0:
			window.selected_text_pointer = 1;
			window.selected_text_first = getSelectedText();
		break;
		case 1:
			window.selected_text_pointer = 0;
			window.selected_text_second = getSelectedText();
		break;
		case undefined:
			window.selected_text_pointer = 0;
			window.selected_text_second = getSelectedText();
		break;
	}
}
