// init controller
var controller = new ScrollMagic.Controller();

// define movement of panels
	var wipeAnimation = new TimelineMax()
		.fromTo(".portfolio_image_wrapper", 1, {x: "0%"}, {x: "-100%", ease: Linear.easeNone})  // in from left
		.fromTo(".portfolio_image_wrapper .vc_column-inner", 1, {x: "0%"}, {x: "50%", ease: Linear.easeNone}, 0); // in from right

	// // create scene to pin and link animation
	new ScrollMagic.Scene({
			triggerElement: ".container-wrap",
			triggerHook: 'onLeave',
			duration: "100%"
		})
		.setPin(".container-wrap .main-content")
		.setTween(wipeAnimation)
		.addTo(controller);