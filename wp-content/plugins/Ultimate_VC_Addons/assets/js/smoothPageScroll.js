/* http://blog.bassta.bg/2013/05/smooth-page-scrolling-with-tweenmax/ */

$(function(){
	
	var $window = $(window);		//Window object
	//console.log('hi');
	var scrollTime = 0.5;			//Scroll time
	var scrollDistance = 400;		//Distance. Use smaller value for shorter scroll and greater value for longer scroll
		
	$window.on("mousewheel DOMMouseScroll", function(event){
	
		event.preventDefault();	
		//console.log(event.originalEvent.wheelDelta);					
		var delta = event.originalEvent.wheelDelta/120 || -event.originalEvent.detail/3;
		var scrollTop = $window.scrollTop();
		var finalScroll = scrollTop - parseInt(delta*scrollDistance);
			//console.log(finalScroll);
		TweenMax.to($window, scrollTime, {
			scrollTo : { y: finalScroll, autoKill:true },
				ease: Power1.easeOut,	//For more easing functions see http://api.greensock.com/js/com/greensock/easing/package-detail.html
				autoKill: true,
				overwrite: 5							
			});
					
	});


	jQuery(".ult_exp_section").select(function(event){
		event.preventDefault();	
		var scrollTop = $window.scrollTop();
		console.log(scrollTop);
		
		if(jQuery(this).parent().find('.ult_exp_content').hasClass('ult_active_section')){
			var delta = jQuery(this).parent().find('.ult_exp_content').outerHeight()/120;
		}
		else{
			var delta = -jQuery(this).parent().find('.ult_exp_content').outerHeight()/120;

		}
		/*console.log(delta);
		var finalScroll = scrollTop - parseInt(delta);
		TweenMax.to($window, scrollTime, {
			scrollTo : { y: finalScroll, autoKill:true },
				ease: Power1.easeOut,	//For more easing functions see http://api.greensock.com/js/com/greensock/easing/package-detail.html
				autoKill: true,
				overwrite: 5							
			});*/
		//var delta = event.originalEvent.wheelDelta/120 || -event.originalEvent.detail/3;
       // console.log(jQuery(this).parent().find('.ult_exp_content').outerHeight());

    });
	
});