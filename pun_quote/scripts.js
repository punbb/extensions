
/***********************************************************************

		Copyright (C) 2008  PunBB
		
		PunBB is free software; you can redistribute it and/or modify it
		under the terms of the GNU General Public License as published
		by the Free Software Foundation; either version 2 of the License,
		or (at your option) any later version.
		
		PunBB is distributed in the hope that it will be useful, but
		WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 59 Temple Place, Suite 330, Boston,
		MA  02111-1307  USA

***********************************************************************/

document.onmouseup = SetSelected;

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
	var result = '';
	if (document.selection)
		result = document.selection.createRange().text;
	else if (document.getSelection)
		result = document.getSelection();
	else if (window.getSelection)
		result = window.getSelection();
	else
		return result;
	return result;
}

function TrimString(param)
{
	param = param.replace(/ /g,' ');
	return param.replace(/(^\s+)|(\s+$)/g, '');
}

function Reply(tid_param, qid_param)
{
	var element = document.getElementsByTagName('div');
	for (var i=0; i < element.length; i++)
	{
		if(element[i].className.match(/^post\s.*/ig))
		{
	
			var post = new String(element[i].innerHTML);
	
			if(post.search('Reply[(]' + tid_param + ',' + qid_param + '[)]') != -1)
			{
				post=ChangePost(post);
				var post_new = RemoveSymbols(post);
				var selected_text = (window.selected_text_first == '')?window.selected_text_second:window.selected_text_first;//getSelectedText();
				var reply_url = document.getElementById("pun_quote_url");
				reply_url = reply_url.value;
				var replace_url;
	
				if((selected_text != undefined)&&(selected_text!=''))
				{
					//this is for Chrome browser. Text, selected by user, has 'Range' type, not 'String'. And in some cases, when there is no text selected, Chrome returns one symbol of 'Caret' type
					if((selected_text.type=='Range')||(selected_text.type=='Caret'))
					selected_text=selected_text.toString();
					
					selected_text = RemoveSymbols(selected_text);
					
					post = TrimString(post);
					
					if((post_new.indexOf(selected_text) != -1) && (selected_text.charAt(0) != ''))
					{
						var form = document.getElementById('qq');
						//form.action='post.php?tid=' + tid_param + '&qid=' + qid_param;
						replace_url = reply_url.replace('$2',qid_param.toString());
						form.action = replace_url.toString();
						element = document.getElementById('post_msg');
						element.value =(window.selected_text_first == '')?window.selected_text_second:window.selected_text_first;//getSelectedText();
						form.submit();
						break;
					}
				}
				replace_url = reply_url.replace('$2',qid_param.toString());
				location = replace_url.toString();
			}
		}
	}
	
	return false;
}

function QuickQuote(tid_param, qid_param)
{
	var element = document.getElementsByTagName('div');

	for (var i=0; i < element.length; i++)
	{

		if (element[i].className.match(/^post\s.*/ig))
		{
		
			var post = new String(element[i].innerHTML);
			if (post.search('QuickQuote[(]' + tid_param + ',' + qid_param + '[)]') != -1)
			{
				//get quoted author name from the post
				//var RegExp = /<cite>.*\sby\s(.*?):/ig;  old markup compatibility
				var RegExp =/<span class="*post-byline"*>(?:.*?)<a(?:.*?)>(.*?)<\/a>/ig;
				var result =  RegExp.exec(post);
				RegExp.lastIndex=0;
				var author_name;
				
				if(result!=null)
					author_name=result[1];


				post=ChangePost(post);
				var post_new = RemoveSymbols(post);
				var selected_text = (window.selected_text_first == '')?window.selected_text_second:window.selected_text_first;//getSelectedText();


				post = TrimString(post);

				if ((selected_text != undefined)&&(selected_text!=''))
				{
					//this is for Chrome browser. Text, selected by user, has 'Range' type, not 'String'. And in some cases, when there is no text selected, Chrome returns one symbol of 'Caret' type
					if((selected_text.type=='Range') || (selected_text.type=='Caret'))
						selected_text=selected_text.toString();
	
					selected_text = RemoveSymbols(selected_text);

					if ((post_new.indexOf(selected_text) != -1) && (selected_text.charAt(0) != ''))
					{
						element = document.getElementById('fld1');
						element.value +=(window.selected_text_first == '')?'[quote='+author_name+']'+window.selected_text_second+'[/quote]'+'\n':'[quote='+author_name+']'+window.selected_text_first+'[/quote]'+'\n';//getSelectedText();
						break;
					}
				}
				element = document.getElementById('fld1');
				element.value+='[quote='+author_name+']'+post+'[/quote]'+'\n';
			}
		}
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
function ChangePost(post)
{
	var reg = new RegExp('<DIV[\\s]*class[\\s]*=[\\s]*["]*[\\s]*entry\\-content[\\s]*["]*[\\s]*>[\\s\\S]*<DIV[\\s]*class[\\s]*=[\\s]*["]*[\\s]*postfoot[\\s]*["]*[\\s]*>','ig');
	
	var post = new String(reg.exec(post));
	
	var browse = navigator.userAgent.toLowerCase();
	
	post = post.replace(/((<BR>)(<\/P>))|((<BR\/>)(<\/P>))/ig,'$2$4');
	
	if(browse.indexOf('opera') == -1)
		post = post.replace(/((<BR>)(<P>))|((<BR\/>)(<P>))/ig,'$2$4');
	
	post = post.replace(/(:?<BR>)|(:?<BR\/>)/ig,'\n');
	
	//</p><p> = \n\n  - Opera FF
	//</p><p> = /n - IE 7.0
	if(browse.indexOf('opera') != -1 ||  browse.indexOf('gecko') != -1)
		post = post.replace(/(:?<\/p>)|(:?<p>)/ig,'\n');
	else
		post = post.replace(/<\/p>[\s]*<p>/ig,'\n');
	
	post = post.replace(/>[\s]*</,'><');
	
	//Make [quote="name"]...[/quote]
	post = post.replace(/<div[\s]*class[\s]*=[\s]*["]*[\s]*quotebox[\s]*["]*[\s]*>[\s]*<cite>/ig,'[quote=');
	post = post.replace(/<div[\s]*class[\s]*=[\s]*["]*[\s]*quotebox[\s]*["]*[\s]*>/ig,'[quote]');
	post = post.replace(/[\s]*wrote:/g,"]");
	post = post.replace(/[\s]*<\/blockquote>[\s]*/ig,'[/quote]\n');
	
	//Remove signature block
	post = post.replace(/<div[\s]*class[\s]*=[\s]*["]*[\s]*sig-content[\s]*["]*[\s]*>(:?.*?)<\/div>/gi,'');
	
	//Remove tags
	post = post.replace(/<(:?.*?)>/gi,'');
	
	//Replace quote = name name on quote = "name name"
	post = post.replace(/\[quote=(["][-a-zA-Z0-9]*)[\s]+([-"a-zA-Z0-9]*)\]/g,'[quote=\"$1 $2\"]');
	
	//Insert \n before [/quote]
	post = post.replace(/\]\[\/quote\]/g,']\n[/quote]');
	
	//exotic symbols =)
	post = post.replace(/\u00A0/g,' ');
	post = post.replace(/&nbsp;/g,' ');
	post = post.replace(/&lt;/g,'<');
	post = post.replace(/&gt;/g,'>');
	return post;
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
