var page = require('webpage').create();
var system = require('system');
page.onConsoleMessage = function(msg) {
  console.log('' + msg);
};
var mypage = system.args[1];
page.open('https://www.binnews.ninja/films-hd', function(status) {
	 page.evaluate(function(mypage) {
		var lim = document.getElementsByName("lim");
		for(q = 0; q < lim.length; q++){
			lim[q].value=mypage;
		}
		var PageChoix = document.getElementsByName("PageChoix");
		for(q = 0; q < PageChoix.length; q++){
			PageChoix[q].submit();
		}
	}, mypage);
	setTimeout(function(){
		console.log(page.content);
		phantom.exit();
	}, 500);
});

