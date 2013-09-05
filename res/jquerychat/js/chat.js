/*

Copyright (c) 2009 Anant Garg (anantgarg.com | inscripts.com)

This script may be used for non-commercial purposes only. For any
commercial purposes, please contact the author at 
anant.garg@inscripts.com

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

*/

var windowFocus = true;
var username;
var chatHeartbeatCount = 0;
var minChatHeartbeat = 1000;
var maxChatHeartbeat = 33000;
var chatHeartbeatTime = minChatHeartbeat;
var originalTitle;
var blinkOrder = 0;
// Width of a single box
var basicBoxWidth = 160;
var chatPhpUrl = 'index.php?type=5000';

var chatboxFocus = new Array();
var newMessages = new Array();
var newMessagesWin = new Array();
var namesWin = new Array();
var chatBoxes = new Array();

$(document).ready(function(){
	originalTitle = document.title;
	startChatSession();

	$([window, document]).blur(function(){
		windowFocus = false;
	}).focus(function(){
		windowFocus = true;
		document.title = originalTitle;
	});
});

function restructureChatBoxes() {
	align = 0;
	for (x in chatBoxes) {
		chatboxtitle = chatBoxes[x];

		if ($("#chatbox_"+chatboxtitle).css('display') != 'none') {
			if (align == 0) {
				$("#chatbox_"+chatboxtitle).css('right', '20px');
			} else {
				width = (align)*(basicBoxWidth+7)+20;
				$("#chatbox_"+chatboxtitle).css('right', width+'px');
			}
			align++;
		}
	}
}

function chatWith(chatboxid, chatuser) {
	createChatBox(chatboxid, chatuser);
	$("#chatbox_"+chatboxid+" .chatboxtextarea").focus();
}

function createChatBox(chatboxid,chatboxtitle,minimizeChatBox) {
	if ($("#chatbox_"+chatboxid).length > 0) {
		if ($("#chatbox_"+chatboxid).css('display') == 'none') {
			$("#chatbox_"+chatboxid).css('display','block');
			restructureChatBoxes();
		}
		$("#chatbox_"+chatboxid+" .chatboxtextarea").focus();
		return;
	}

	$(" <div />" ).attr("id","chatbox_"+chatboxid)
	.addClass("chatbox")
	.html('<div class="chatboxhead"><div class="chatboxtitle">'+chatboxtitle+'</div><div class="chatboxoptions"><a href="javascript:void(0)" onclick="javascript:toggleChatBoxGrowth(\''+chatboxid+'\')"><img src="typo3conf/ext/typo3chat/res/icons/minimizechat.png" height="16"/></a> <a href="javascript:void(0)" onclick="javascript:closeChatBox(\''+chatboxid+'\')"><img src="typo3conf/ext/typo3chat/res/icons/closechat.png" height="16"/></a></div><br clear="all"/></div><div class="chatboxcontent"></div><div class="chatboxinput"><textarea class="chatboxtextarea" onkeydown="javascript:return checkChatBoxInputKey(event,this,\''+chatboxid+'\');"></textarea></div>')
	.appendTo($( "body" ));
	
	$("#chatbox_"+chatboxid).css('bottom', '0px');
	
	chatBoxeslength = 0;

	for (x in chatBoxes) {
		if ($("#chatbox_"+chatBoxes[x]).css('display') != 'none') {
			chatBoxeslength++;
		}
	}

	if (chatBoxeslength == 0) {
		$("#chatbox_"+chatboxid).css('right', '20px');
	} else {
		width = (chatBoxeslength)*(basicBoxWidth+7)+20;
		$("#chatbox_"+chatboxid).css('right', width+'px');
	}
	
	chatBoxes.push(chatboxid);

	if (minimizeChatBox == 1) {
		minimizedChatBoxes = new Array();

		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);
		}
		minimize = 0;
		for (j=0;j<minimizedChatBoxes.length;j++) {
			if (minimizedChatBoxes[j] == chatboxid) {
				minimize = 1;
			}
		}

		if (minimize == 1) {
			$('#chatbox_'+chatboxid+' .chatboxcontent').css('display','none');
			$('#chatbox_'+chatboxid+' .chatboxinput').css('display','none');
		}
	}

	chatboxFocus[chatboxid] = false;

	$("#chatbox_"+chatboxid+" .chatboxtextarea").blur(function(){
		chatboxFocus[chatboxid] = false;
		$("#chatbox_"+chatboxid+" .chatboxtextarea").removeClass('chatboxtextareaselected');
	}).focus(function(){
		chatboxFocus[chatboxid] = true;
		newMessages[chatboxid] = false;
		$('#chatbox_'+chatboxid+' .chatboxhead').removeClass('chatboxblink');
		$("#chatbox_"+chatboxid+" .chatboxtextarea").addClass('chatboxtextareaselected');
	});
	namesWin[chatboxid] = chatboxtitle;
	
	$("#chatbox_"+chatboxid).click(function() {
		if ($('#chatbox_'+chatboxid+' .chatboxcontent').css('display') != 'none') {
			$("#chatbox_"+chatboxid+" .chatboxtextarea").focus();
		}
	});

	// We need the chat history
	if (minimizeChatBox != 1) {
		$.ajax({
	
		  url: chatPhpUrl+"&method=typo3chat::pi1::chathistory",
		  type: "POST",
		  data: { to: chatboxid },
		  cache: false,
		  dataType: "json",
		  success: function(data) {
			  
			$.each(data.items, function(i,item) {
				if (item)	{ // fix strange ie bug

					mychatboxid = item.f;
					mychatboxtitle = item.n;
					
					if (item.s == 1) {
						item.n = username;
					}

					$("#chatbox_"+mychatboxid+" .chatboxcontent").append(constructItem(item));
					
					$("#chatbox_"+mychatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+mychatboxid+" .chatboxcontent")[0].scrollHeight);
				}
			});
		 }
		});
	}
	
	$("#chatbox_"+chatboxid).show();
}


function chatHeartbeat(){

	var itemsfound = 0;
	
	if (windowFocus == false) {
 
		var blinkNumber = 0;
		var titleChanged = 0;
		for (x in newMessagesWin) {
			if (newMessagesWin[x] == true) {
				++blinkNumber;
				if (blinkNumber >= blinkOrder) {
					document.title = '*** '+originalTitle;
					titleChanged = 1;
					break;	
				}
			}
		}
		
		if (titleChanged == 0) {
			document.title = originalTitle;
			blinkOrder = 0;
		} else {
			++blinkOrder;
		}

	} else {
		for (x in newMessagesWin) {
			newMessagesWin[x] = false;
		}
	}

	for (x in newMessages) {
		if (newMessages[x] == true) {
			if (chatboxFocus[x] == false) {
				//FIXME: add toggle all or none policy, otherwise it looks funny
				$('#chatbox_'+x+' .chatboxhead').toggleClass('chatboxblink');
			}
		}
	}
	
	$.ajax({
	  url: chatPhpUrl+"&method=typo3chat::pi1::chatheartbeat",
	  cache: false,
	  dataType: "json",
	  success: function(data) {

		$.each(data.items, function(i,item){
			if (item)	{ // fix strange ie bug

				chatboxid = item.f;
				chatboxtitle = item.n;

				if ($("#chatbox_"+chatboxid).length <= 0) {
					createChatBox(chatboxid, chatboxtitle);
				}
				if ($("#chatbox_"+chatboxid).css('display') == 'none') {
					$("#chatbox_"+chatboxid).css('display','block');
					restructureChatBoxes();
				}
				
				if (item.s == 1) {
					item.n = username;
				}

				$("#chatbox_"+chatboxid+" .chatboxcontent").append(constructItem(item));
				
				if (item.s != 2) {
					newMessages[chatboxid] = true;
					newMessagesWin[chatboxid] = true;
				}

				$("#chatbox_"+chatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+chatboxid+" .chatboxcontent")[0].scrollHeight);
				itemsfound += 1;
			}
		});

		chatHeartbeatCount++;

		if (itemsfound > 0) {
			chatHeartbeatTime = minChatHeartbeat;
			chatHeartbeatCount = 1;
		} else if (chatHeartbeatCount >= 10) {
			chatHeartbeatTime *= 2;
			chatHeartbeatCount = 1;
			if (chatHeartbeatTime > maxChatHeartbeat) {
				chatHeartbeatTime = maxChatHeartbeat;
			}
		}
		
		setTimeout('chatHeartbeat();',chatHeartbeatTime);
	}});
}

function closeChatBox(chatboxid) {
	$('#chatbox_'+chatboxid).css('display','none');
	restructureChatBoxes();

	$.post(chatPhpUrl+"&method=typo3chat::pi1::closechat", { chatbox: chatboxid} , function(data){	
	});

}

function toggleChatBoxGrowth(chatboxid) {
	if ($('#chatbox_'+chatboxid+' .chatboxcontent').css('display') == 'none') {  
		
		var minimizedChatBoxes = new Array();
		
		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);
		}

		var newCookie = '';

		for (i=0;i<minimizedChatBoxes.length;i++) {
			if (minimizedChatBoxes[i] != chatboxid) {
				newCookie += chatboxid+'|';
			}
		}

		newCookie = newCookie.slice(0, -1)


		$.cookie('chatbox_minimized', newCookie);
		$('#chatbox_'+chatboxid+' .chatboxcontent').css('display','block');
		$('#chatbox_'+chatboxid+' .chatboxinput').css('display','block');
		$("#chatbox_"+chatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+chatboxid+" .chatboxcontent")[0].scrollHeight);
	} else {
		
		var newCookie = chatboxid;

		if ($.cookie('chatbox_minimized')) {
			newCookie += '|'+$.cookie('chatbox_minimized');
		}


		$.cookie('chatbox_minimized',newCookie);
		$('#chatbox_'+chatboxid+' .chatboxcontent').css('display','none');
		$('#chatbox_'+chatboxid+' .chatboxinput').css('display','none');
	}
	
}

function checkChatBoxInputKey(event,chatboxtextarea,chatboxid) {
	var item;
	
	if(event.keyCode == 13 && event.shiftKey == 0)  {
		message = $(chatboxtextarea).val();
		message = message.replace(/^\s+|\s+$/g,"");

		$(chatboxtextarea).val('');
		$(chatboxtextarea).focus();
		$(chatboxtextarea).css('height','44px');
		if (message != '') {
			$.post(chatPhpUrl+"&method=typo3chat::pi1::sendchat", {to: chatboxid, message: message} , function(data){
			});
			message = message.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\"/g,"&quot;");
			item = new Array();
			item.f = chatboxid;
			item.n = username;
			item.m = message;
			var d = new Date();	var curr_hour = d.getHours(); var curr_min = d.getMinutes();
			if (curr_hour < 10) curr_hour = '0'+curr_hour;
			if (curr_min < 10) curr_min = '0'+curr_min;

			item.t = curr_hour+':'+curr_min;
			item.s = 1; 
			$("#chatbox_"+chatboxid+" .chatboxcontent").append(constructItem(item));
			$("#chatbox_"+chatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+chatboxid+" .chatboxcontent")[0].scrollHeight);
		}
		chatHeartbeatTime = minChatHeartbeat;
		chatHeartbeatCount = 1;

		return false;
	}

	var adjustedHeight = chatboxtextarea.clientHeight;
	var maxHeight = 94;

	if (maxHeight > adjustedHeight) {
		adjustedHeight = Math.max(chatboxtextarea.scrollHeight, adjustedHeight);
		if (maxHeight)
			adjustedHeight = Math.min(maxHeight, adjustedHeight);
		if (adjustedHeight > chatboxtextarea.clientHeight)
			$(chatboxtextarea).css('height',adjustedHeight+8 +'px');
	} else {
		$(chatboxtextarea).css('overflow','auto');
	}
	 
}

function constructItem(item) {
	if (item.s == 2) {
		return '<div class="chatboxmessage"><div class="chatboxinfo">'+item.m+'</div></div>';
	} else if (item.s == 0) {
		return '<div class="chatboxmessage"><div class="chatboxmessagehead"><div class="chatboxmessagefrom"><span class="fromSender">'+item.n+':</span></div><div class="chattimestamp">'+item.t+'</div><br clear="all"/></div><div class="chatboxmessagecontent"><span class="fromSender">'+item.m+'</span></div></div>';
	}
	return '<div class="chatboxmessage"><div class="chatboxmessagehead"><div class="chatboxmessagefrom"><span class="fromMyself">'+item.n+':</span></div><div class="chattimestamp">'+item.t+'</div><br clear="all"/></div><div class="chatboxmessagecontent"><span class="fromMyself">'+item.m+'</span></div></div>';
}

function startChatSession(){  
	$.ajax({
	  url: chatPhpUrl+"&method=typo3chat::pi1::startchatsession",
	  cache: false,
	  dataType: "json",
	  success: function(data) {
 
		username = data.username;

		$.each(data.items, function(i,item){
			if (item)	{ // fix strange ie bug

				chatboxid = item.f;
				chatboxtitle = item.n;

				if ($("#chatbox_"+chatboxid).length <= 0) {
					createChatBox(chatboxid,chatboxtitle,1);
				}
				
				if (item.s == 1) {
					item.n = username;
				}

				$("#chatbox_"+chatboxid+" .chatboxcontent").append(constructItem(item));
			}
		});
		
		for (i=0;i<chatBoxes.length;i++) {
			chatboxid = chatBoxes[i];
			$("#chatbox_"+chatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+chatboxid+" .chatboxcontent")[0].scrollHeight);
			setTimeout('$("#chatbox_"+chatboxid+" .chatboxcontent").scrollTop($("#chatbox_"+chatboxid+" .chatboxcontent")[0].scrollHeight);', 100); // yet another strange ie bug
		}
	
	setTimeout('chatHeartbeat();',chatHeartbeatTime);
		
	}});
}

/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};