if (typeof FORUM === "undefined" || !FORUM) {
	var FORUM = {};
}

FORUM.toggle_ext = function() {
	
    Array.prototype.unique = function() {
        var a = [], l = this.length;
        for(var i=0; i<l; i++) {
            for(var j=i+1; j<l; j++)
                if (this[i] === this[j]) j = ++i;
            a.push(this[i]);
        }
        return a;
    };
    
    function updateCookie(collapsed){
    	if (collapsed.length > 0) {
    		createCookie('collapsed',collapsed.unique().join(":"),365);
    	} else {
    		eraseCookie('collapsed');
    	}
    }
    
    function createCookie(name,value,days) {
    	if (days) {
    		var date = new Date();
    		date.setTime(date.getTime()+(days*24*60*60*1000));
    		var expires = "; expires="+date.toGMTString();
    	}
    	else var expires = "";
    	document.cookie = name+"="+value+expires+"; path=/";
    }

    function readCookie(name) {
    	var nameEQ = name + "=";
    	var ca = document.cookie.split(';');
    	for(var i=0;i < ca.length;i++) {
    		var c = ca[i];
    		while (c.charAt(0)==' ') c = c.substring(1,c.length);
    		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    	}
    	return null;
    }

    function eraseCookie(name) {
    	createCookie(name,"",-1);
    }    
    
	return {
		init : function() {
			
			var collapsed = readCookie("collapsed");
			if (!collapsed || collapsed == undefined || collapsed.length < 1) {
				collapsed = new Array();
			} else {
				collapsed = collapsed.split(":");
			}

			$(".collapsable")
				.click(
					function() {
						var cid = $(this).attr("id");
						
						if (!cid) {
							return;
						}
					
						if ($(this).hasClass("collapsed")) {
							$(this).show(function() {
								$("."+cid).animate({
									height : "show"
								}, 200, "swing", function(){
									$.each(collapsed, function(i, val){
										if (val==cid){
											collapsed.splice(i,1);
										}
									});
									updateCookie(collapsed);
								});
								$(this).removeClass("collapsed");
							});
						} else {
							$("."+cid).animate({
								height : "hide"
							}, 200, "swing",function(){
								$(this).prev().addClass("collapsed");
								collapsed.push(cid);
								updateCookie(collapsed);
							});
						}	
			});
		}
	};
}();
jQuery(function() {
	FORUM.toggle_ext.init();
});