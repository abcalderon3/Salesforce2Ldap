$('#my-email').html(function(){
	var e = "abc3";
	var a = "@";
	var d = "adriancalderon";
	var c = ".org";
	var h = 'mailto:' + e + a + d + c;
	$(this).parent('a').attr('href', h);
	return e + a + d + c;
});