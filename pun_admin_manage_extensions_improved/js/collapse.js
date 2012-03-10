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
    
	return {
		init : function() {
			
			var collapsed = $.cookie("collapsed");
			if (!collapsed || collapsed == undefined || collapsed.length < 1) {
				collapsed = new Array();
			} else {
				collapsed = collapsed.split(":");
				collapsed = $.map(collapsed, function(val, i) {
					return parseInt(val, 10);
				});
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
								}, 300, "swing");
								$(this).removeClass("collapsed");
							});
							$.each(collapsed, function(i, val){
								if (val=cid){
									collapsed.splice(i,1);
								}
							});
						} else {
							$("."+cid).animate({
								height : "hide"
							}, 300, "swing",function(){
										$(this).prev().addClass("collapsed");
							});
							collapsed.push(cid);
						}	
						
						if (collapsed.length > 0) {
							cookie = collapsed.unique().join(":");
						} else {
							cookie = null;
						}
						$.cookie("collapsed", cookie, {
							expires : 99999,
							path : "/"
						});
			});
		}
	};
}();
jQuery(function() {
	FORUM.toggle_ext.init();
});