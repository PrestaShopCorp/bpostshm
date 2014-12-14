{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
<script type="text/javascript">
	// <![CDATA[ 
	var fancyboxParams,
		id_carrier			= {$id_carrier|intval|default:0},
		opc 				= {if $opc}true{else}false{/if},
		service_point_id	= {$service_point_id|intval|default:0},
		shipping_methods 	= {$shipping_methods|json_encode},
		version 			= {$version|floatval},
		l_s 				= {
			"Select delivery point" : "{l s='Select delivery point' mod='bpostshm' js=1}",
			"Change delivery point" : "{l s='Change delivery point' mod='bpostshm' js=1}",
			"You must agree to the terms of service before continuing." : "{l s='You must agree to the terms of service before continuing.' mod='bpostshm' js=1}"
		},
		$button 			= $('<a id="bpostHandler" class="button" style="margin: 5px 0 2px;" />'),
		$carrierInput,
		$carrierInputs;

	fancyboxParams = {
		autoWidth:		true,
		centerOnScroll: true,
		minHeight: 		400,
		helpers : {
			title : null
		},
		live:			false,
		type:			'iframe',
		width:			1120,
		onStart: 		function() {
			return this.beforeLoad();
		},
		beforeLoad: 	function() {
			if (!(acceptCGV()))
				return false;

			this.href = $('#bpostHandler').data('href');
			return true;
		}
	};

	if (version < 1.5)
	{
		fancyboxParams.autoScale = true;
		fancyboxParams.height = 625;
	}

	$button = myButton($button);
	$button.data('href', '{$url_lightbox|escape:'javascript'}');
	$button.fancybox(fancyboxParams);

	$carrierInputs = $('.delivery_option_radio');
	if (version < 1.5)
		$carrierInputs = $('[name="id_carrier"]');
	$carrierInput = $carrierInputs.filter(function() { return parseInt(this.value, 10) === id_carrier; });

	if ('undefined' === typeof acceptCGV)
		function acceptCGV()
		{
			if ($('#cgv').length && !$('input#cgv:checked').length)
			{
				alert(l_s['You must agree to the terms of service before continuing.']);
				return false;
			}
			else
				return true;
		}

	function myButton($button_, content)
	{
		if ('undefined' === typeof content)
			content = l_s['Select delivery point'];

		$button_.attr('title', content);

		if (version > 1.5)
			$button_.addClass('button-small').html('<span>'+content+'</span>');
		else
		{
			if (version < 1.5)
				$button_.addClass('button exclusive_large');

			$button_.text(content);
		}

		return $button_;
	}

	function showButton($carrier, shipping_method)
	{
		var href = $button.data('href'),
			$button_,
			$container;

		href = href.replace(/(shipping_method=).*?(&)/,'$1' + shipping_method + '$2');
		$is_mobile = $('html.ui-mobile').length;
			
		if (version < 1.5)
			$container = $carrier.find('.carrier_infos');
		else
		{
			if ($is_mobile)
				$container = $carrier;
			else
				$container = $carrier.find('.delivery_option_logo').next('td');
		}

		if (version >= 1.6)
			$container.append('<br />');

		$button_ = $button.clone(true);
		$carrierAtStart = $carrierInputs.filter(function() { return parseInt(this.value, 10) === carrierAtStart && $(this).is(':checked'); });

		if (onLoad && $carrierAtStart.length && service_point_id)
			myButton($button_, l_s['Change delivery point']);

		$button_.data('href', href);
		if ($is_mobile)
			$button_.attr('class', 'ui-btn ui-btn-inner ui-btn-up-c ui-shadow');
			// $button_.attr('style', 'background: #18c;color: #eff;padding: 4px 6px;margin:0 auto;');

		$container.append('<br />', $button_);

		$('[name="processCarrier"]').attr('disabled', true).css('opacity', .3);
	}

	$(function() {
		if ('undefined' === typeof onLoad)
			onLoad = true;

		if ('undefined' === typeof carrierAtStart)
			carrierAtStart = id_carrier;

		if (opc)
		{
			var $carrier, shipping_method;

			$.each(shipping_methods, function(i, row) {
				if (row === id_carrier)
				{
					shipping_method = i;
					return false;
				}
				return true;
			});

			if (shipping_method)
			{
				if (version < 1.5)
					$carrier = $carrierInput.closest('tr');
				else
					$carrier = $carrierInput.closest('.delivery_option');

				showButton($carrier, shipping_method);
			}

			$carrierAtStart = $carrierInputs.filter(function() { return parseInt(this.value, 10) === carrierAtStart && $(this).is(':checked'); });

			if ($carrierAtStart.length && service_point_id)
				myButton($('#bpostHandler'), l_s['Change delivery point']);
		}
		else
		{
			$carrierInputs.live('change', function() {
				$('#bpostHandler').prev('br').andSelf().remove();
				$('[name="processCarrier"]').removeAttr('disabled').css('opacity', 1);
			});

			if (onLoad)
			{
				$.each(shipping_methods, function(shipping_method, idCarrier) {
					var $input = $carrierInputs.filter(function() { return parseInt(this.value, 10) === idCarrier; }),
						$carrier;

					if (!$input.length)
						return;

					if (version < 1.5)
						$carrier = $input.closest('tr');
					else
						$carrier = $input.closest('.delivery_option');

					if (idCarrier === carrierAtStart)
						showButton($carrier, shipping_method);

					$input.bind('change.bpost', function() {
						setTimeout(function(e) {
							if ($input.is(':checked'))
								showButton($carrier, shipping_method);
						}, 100);
					});
				});
			}
		}

		if (onLoad)
		{
			$carrierInput.attr('checked', true);
			onLoad = false;
		}
	});
	// ]]>
</script>