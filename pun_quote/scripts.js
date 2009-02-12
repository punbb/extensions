/**
 * Allows users to quote posts without a page reloading
 *
 * @copyright Copyright (C) 2008 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package pun_quote
 */

var selNode = null;

turnOnLinks();

function turnOnLinks()
{
	var p = document.getElementsByTagName('p');

	var tmp_k = 0;

	for (var k = 0; k < p.length; k++ )
	{
		if (p[k].className.match(/post-actions/))
		{
			var span = p[k].getElementsByTagName('span');
			var last_span = span[span.length-1];
			var last_span2 = span[span.length-2];
			var prev_span = span[span.length-4];
			
			prev_span.style.display = "";
			var a = last_span2.getElementsByTagName('a');
			a[0].href = "javascript: QuickQuote()";
	
			/*
			if (tmp_k == 0)
			{
				prev_span.style.display = "";
				tmp_k = 1;
				var a = last_span.getElementsByTagName('a');

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
				prev_span.style.display = "";
				var a = last_span.getElementsByTagName('a');
				a[0].href = "javascript: QuickQuote()";
			}
			*/
		}
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

function QuickQuote(tid_param, qid_param, d)
{
	var selected_text = getSelectedText();
	var author = pun_quote_authors[qid_param];
	var post_content = ParseMessage(pun_quote_posts[qid_param]);
	var changedContent = ContentCleaning(post_content);
	var element = document.getElementById('fld1');

	if (selected_text != undefined && selected_text != '')
	{
		selected_text = selected_text.toString(); //for Google Chrome & Safari
		var changedSelected = ContentCleaning(selected_text);
		var post_blocks = new Array();
		var post_blocks2 = new Array();
		
		try
		{
			post_blocks = document.getElementsByClassName('postbody');
		}
		catch(err)
		{
			var tempDiv = document.getElementById('brd-main');
			var divList = tempDiv.getElementsByTagName('div');
			
			for(i = 0; i < divList.length; i++)
			{
				if (divList[i].className == 'postbody online')
					post_blocks.push(divList[i]);
					
				if (divList[i].className == 'postbody')
					post_blocks.push(divList[i]);
			}
		}

		thisNode = selNode;
		if (thisNode != null)
		{
			while (thisNode.nodeType != 'div' && thisNode.className != 'entry-content')
				thisNode = thisNode.parentNode;	
		}
		
		for (i = 0; i < post_blocks.length; i++)
		{
			var curr_block = post_blocks[i];
			var children = new Array();

			try
			{
				children = curr_block.getElementsByClassName('entry-content');
			}
			catch(err)
			{
				divList = new Array();
				var divList = curr_block.getElementsByTagName('div');
				for(j = 0; j < divList.length; j++)
				{
					if (divList[j].className == 'entry-content')
					{
						children.push(divList[j]);
						break;
					}
				}
				
			}

			children = children[0];
			if ((thisNode == children) && (changedContent.indexOf(changedSelected) != -1))
			{
				element.value += '[quote=' + author + ']' + selected_text + '[/quote]';
				return;
			}
				
		}
	}

	element.value += '[quote=' + author + ']' + post_content + '[/quote]';
}

function Reply(tid_param, qid_param, d)
{
	var selected_text = getSelectedText();
	var post_content = pun_quote_posts[qid_param];
	var changedContent = ContentCleaning(post_content);
	var element = document.getElementById('post_msg');
	element.value = '';
	
	var form = document.getElementById('pun_quote_form');
	var reply_url = document.getElementById('pun_quote_url').value;
	replace_url = reply_url.replace('$2',qid_param.toString());
	form.action = replace_url.toString();
	
	if (selected_text != undefined && selected_text != '')
	{
		selected_text = selected_text.toString(); //for Google Chrome & Safari
		var changedSelected = ContentCleaning(selected_text);
		var post_blocks = new Array();
		var post_blocks2 = new Array();
		
		try
		{
			post_blocks = document.getElementsByClassName('postbody');
		}
		catch(err)
		{
			var tempDiv = document.getElementById('brd-main');
			var divList = tempDiv.getElementsByTagName('div');
			
			for(i = 0; i < divList.length; i++)
			{
				if (divList[i].className == 'postbody online')
					post_blocks.push(divList[i]);
					
				if (divList[i].className == 'postbody')
					post_blocks.push(divList[i]);
			}
		}
		
		thisNode = selNode;
	
		if (thisNode != null)
		{
			while (thisNode.nodeType != 'div' && thisNode.className != 'entry-content')
				thisNode = thisNode.parentNode;	
		}
		
		for (i = 0; i < post_blocks.length; i++)
		{
			var curr_block = post_blocks[i];
			var children = new Array();

			try
			{
				children = curr_block.getElementsByClassName('entry-content');
			}
			catch(err)
			{
				divList = new Array();
				var divList = curr_block.getElementsByTagName('div');
				for(j = 0; j < divList.length; j++)
				{
					if (divList[j].className == 'entry-content')
					{
						children.push(divList[j]);
						break;
					}
				}
				
			}
			
			children = children[0];
			
			if ((thisNode == children) && (changedContent.indexOf(changedSelected) != -1))
			{
				element.value += selected_text;
				form.submit();
				return;
			}
				
		}
	}

	element.value += ParseMessage(post_content);
	form.submit();
	return;
}

function ParseMessage(string)
{
	string = string.replace(/\\xc2\\xad/ig,'\n');
	string = string.replace('\0','');
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


