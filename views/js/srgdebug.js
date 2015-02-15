/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Serge, Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

var reRCn = /^(\d{3}-\d{3}-\d{3})|\d{9}$/;
var reDate = /^(19[0-9][0-9]|20[0-2][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/;
var reMobileBE = /^(0{2}32|\+32|32)?0?(468|4[7-9]\d)(\d{6})$/;

srgDebug = {
	idTrace: '',
	init: function (idTrace) {
		this.idTrace = idTrace;

		$('<a href="#' + idTrace + '-content" id="' + idTrace + '"></a>' +
			'<div id="' + idTrace + '-content" style="display: none;"></div>')
			.insertBefore($('script').last());

		$('#' + idTrace).fancybox({
			padding: 10,
			margin: 20,
			closeBtn: false,
			minWidth: 600,
			onStart: function () {
				$('#' + idTrace + '-content').css('white-space', 'nowrap');
			}
		});

	},
	trace: function ($str) {
		$('#' + this.idTrace + '-content').html($str);
		$('#' + this.idTrace).trigger('click');
	},
	printJson: function ($jsn) {
		$lines = '<ul>';
		$.each($jsn, function (key, value) {
			if ('[object Object]' == value)
				$lines += '<li>' + key + ':&nbsp;' + srgDebug.printJson($.parseJSON(JSON.stringify(value))) + '</li>';
			else {
				if (null == value)
					value = '(<em>null</em>)';

				$lines += '<li>' + key + ':&nbsp;<strong>' + value + '</strong></li>';
			}
		});
		return $lines + '</ul>';
	},
	traceJson: function ($jsn) {
		srgDebug.trace(srgDebug.printJson($jsn));
	}

};