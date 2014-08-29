
/**
 * bpack247
 * Authour:  Serge
 * stigmi.eu
 */

$('#trace').fancybox({
	padding: 	10,
	margin: 	20,
	closeBtn: 	false,
	minWidth: 	600,
	onStart: function() {
		$('#trace-content').css('white-space', 'nowrap');
	}
});
	
function trace($str) {
	$('#trace-content').html($str);
	$('#trace').trigger('click');
}


function printJson($jsn) {
	$lines = '<ul>';
	$.each($jsn, function (key, value) {
    	if ('[object Object]' == value)
    		$lines += '<li>' + key + ':&nbsp;' + printJson($.parseJSON(JSON.stringify(value))) +'</li>' ;
    	else {
    		if (null == value)
    			value = '(<em>null</em>)';
    		
    		$lines += '<li>' +key +':&nbsp;<strong>' +value +'</strong></li>';
    	}
    });
    return $lines +'</ul>';
}

//{literal}
var rcRegX = /^[0-9]{3}-[0-9]{3}-[0-9]{3}$/; 
var rcRegN = /^[0-9]{9}$/;
//var reHtml = /\<\!DOCTYPE html(.*)\>(.*)\<\/html>/; 
//{/literal}

					