$(document).ready(function(){

	$('#minify-javascript')
	.unbind('click')
	.bind('click',function() {

		var minifyContent = $('#javascript-to-minify').val(),
			data = {
				minifyContent : minifyContent
			};
		$('#minify-javascript').toggleClass('build');
		$.post('ajax.generate.php', data, function(content) {
			$('#minified-javascript').val(content);
			$('#minify-javascript').toggleClass('build');
		});
	});

	$('#copy-minified-javascript')
	.unbind('click')
	.bind('click',function() {
		$('#minified-javascript').focus().select();
	});
});
