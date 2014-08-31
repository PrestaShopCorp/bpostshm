
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
var reRCn = /^(\d{3}-\d{3}-\d{3})|\d{9}$/;
var reDate = /^(19[0-9][0-9]|20[0-2][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/;
var reMobileBE = /^(0{2}32|\+32)?0?(468|4[7-9]\d)(\d{6})$/;
//{/literal}

					