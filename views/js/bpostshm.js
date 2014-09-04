/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

BpostShm = {
	cache: 	{
		nearest_service_points:	{},
		service_point_hours:	{}
	},
	icon	: '',
	lang	: {},
	map		: '',
	markers	: [],
	points	: null,
	services: {
		get_nearest_service_points:	'',
		get_service_point_hours:	'',
		set_service_point:			''
	},
	shipping_method:	'',
	init: function(points, shipping_method)
	{
		this.points 			= points;
		this.shipping_method 	= shipping_method;

		var mapParams = {
			mapTypeId:	google.maps.MapTypeId.ROADMAP,
			zoom:		12
		};

		if (this.points.list && this.points.list.length && this.points.coords.length)
			mapParams.center = new google.maps.LatLng( this.points.coords[0][0], this.points.coords[0][1] );

		this.map = new google.maps.Map(document.getElementById('map-canvas'), mapParams);

		this.bindEventListeners();

		if (!this.points.list || !this.points.list.length || !this.points.coords.length)
			return;

		this.update();
	},
	update: function(points)
	{
		if ('undefined' !== typeof points)
			this.points = points;

		if (!this.points.list.length || !this.points.coords.length)
			return $('#searchSubmit').after( $('<span class="error">' + this.lang['No results found'] + '</span>') );

		this.updatePointList();
		this.updateGMapMarkers();

		this.map.panTo( new google.maps.LatLng(this.points.coords[0][0], this.points.coords[0][1]) );
		return;
	},
	bindEventListeners: function()
	{
		$('#search-form').live('submit', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var postcode 	= $('#postcode').val(),
				city		= $('#city').val();

			if ('undefined' !== typeof BpostShm.cache.nearest_service_points[postcode + '_' + city])
				return BpostShm.update(BpostShm.cache.nearest_service_points[postcode + '_' + city]);

			$.get(BpostShm.services.get_nearest_service_points, {
				postcode:	postcode,
				city:		city
			}, function(response) {
				if (response)
				{
					BpostShm.cache.nearest_service_points[postcode + '_' + city] = response;
					BpostShm.update(response);
				}
			});
		});

		var $searchInputs = $('#postcode, #city');
		$searchInputs.live('keydown', function() {
			$searchInputs.filter(':not(#' + this.id + ')').val('');
		});

		$('#poi li .button')
			.live('click', function() {
				var $poi = $(this).closest('li');

				$.post(BpostShm.services.set_service_point, {
					service_point_id:	$poi.data('servicepointid')
				}, function(response) {
					try {
						if (parent.$('#form').length)
						{
							parent.$.fancybox.close();

							parent.$('[name="processCarrier"]').remove();
							parent.$('#form').append('<input name="processCarrier" type="hidden" value="1" />').submit();
						}
						else
						{
							var recyclablePackage = 0;
							var gift = 0;
							var giftMessage = '';

							var delivery_option_radio = parent.$('.delivery_option_radio');
							var delivery_option_params = '&';
							$.each(delivery_option_radio, function(i) {
								if ($(this).prop('checked'))
									delivery_option_params += parent.$(delivery_option_radio[i]).attr('name') + '=' + parent.$(delivery_option_radio[i]).val() + '&';
							});
							if (delivery_option_params == '&')
								delivery_option_params = '&delivery_option=&';

							if (parent.$('input#recyclable:checked').length)
								recyclablePackage = 1;
							if (parent.$('input#gift:checked').length)
							{
								gift = 1;
								giftMessage = encodeURIComponent(parent.$('#gift_message').val());
							}

							$.post(parent.orderOpcUrl, {
								ajax:			true,
								gift:			gift,
								gift_message: 	giftMessage,
								id_carrier:		parseInt(parent.$('[name="id_carrier"], .delivery_option_radio').filter(':checked').val(), 10),
								method:			'updateCarrierAndGetPayments',
								passthrough:	true,
								rand:			new Date().getTime(),
								recyclable: 	recyclablePackage
							}, function(response) {
								if (response.hasError)
								{
									var errors = '';
									for(var error in response.errors)
										//IE6 bug fix
										if(error !== 'indexOf')
											errors += $('<div />').html(response.errors[error]).text() + "\n";
									alert(errors);
								}
								else
								{
									parent.updateCartSummary(response.summary);
									parent.updatePaymentMethods(response);
									parent.updateHookShoppingCart(response.HOOK_SHOPPING_CART);
									parent.updateHookShoppingCartExtra(response.HOOK_SHOPPING_CART_EXTRA);
									parent.updateCarrierList(response.carrier_data);
									parent.$('#opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
									parent.refreshDeliveryOptions();
								}

								parent.$.fancybox.close();
							}, 'JSON');
						}
					} catch(err) {
						parent.$('#fancybox-overlay').hide();
						parent.$('#fancybox-wrap').hide();
					}
				}, 'JSON');
			});

		$('#poi li').live('click', function(e) {
			if ($(e.target).is('.button'))
				return;
			else if ($(e.target).is('a'))
			{
				e.preventDefault();
				e.stopPropagation();
			}

			// Retrieve and display OR hide hours
			var $poi 	= $(this),
				$hours 	= $poi.find('.hours');

			$poi.siblings().removeClass('active').find('.hours').hide();
			$poi.toggleClass('active');

			if ($hours.length)
				$hours.toggle();

			else
				switch (BpostShm.shipping_method)
				{
					// if SHIPPING_METHOD_AT_SHOP
					case 2:
						if ('undefined' !== typeof BpostShm.cache.service_point_hours[ $poi.data('servicepointid') ])
							return BpostShm.appendHours(BpostShm.cache.service_point_hours[ $poi.data('servicepointid') ], $poi);

						$.get(BpostShm.services.get_service_point_hours, {
							service_point_id: $poi.data('servicepointid')
						}, function(response) {
							if (response)
							{
								BpostShm.cache.service_point_hours[ $poi.data('servicepointid') ] = response;
								BpostShm.appendHours(response, $poi);
							}
						});
						break;

					// if SHIPPING_METHOD_AT_24_7
					case 4:
						$button = $('<a />').addClass('button').text(BpostShm.lang['Next step']);
						$('<div class="hours" />').append($button).insertAfter( $poi.find('a') );
						break;
				}

			$('#poi').scrollTo($poi);
		});
	},
	updatePointList: function()
	{
		if (this.points.list.length)
		{
			var $poiList = $('#poi'),
				$poi	 = $poiList.children('li').first().clone(),
				$poi_tmp;

			$poiList.empty();
			$poi.removeClass('active hidden');

			$.each(this.points.list, function(i, row) {
				$poi_tmp = $poi.clone(true);
				$poi_tmp
					.data('servicepointid', row.id)
					.attr('data-servicepointid', row.id)
					.find('a')
						.attr('title', row.office)
						// Bind marker animation
						.click(function() {
						 	var marker 		= BpostShm.markers[i],
								animation 	= google.maps.Animation.BOUNCE;

							if ('undefined' !== typeof marker && null !== marker.getAnimation())
								animation = null;

							$(BpostShm.markers).each(function() {
								this.setAnimation(null);
							});
							marker.setAnimation(animation);
						})
						.end()
					// Fill row with place information
					.find('.details')
						.empty()
						.append(
							$('<span />').addClass('name').text(row.office),
							row.street+' '+row.nr,
							'<br />',
							row.zip+' '+row.city
						)
						.end()
					.find('.hours')
						.remove();

				$poiList.append($poi_tmp);
			});
		}
	},
	updateGMapMarkers: function()
	{
		if (this.points.coords.length)
		{
			this.removeMarkers();

			$.each(this.points.coords, function(i, coords) {
				setTimeout(function() {
					BpostShm.addMarker(coords);
				}, i * 50);
			});
		}
	},
	addMarker: function(coords) {
		var marker = new google.maps.Marker({
			animation:	google.maps.Animation.DROP,
			icon:		this.icon,
			map:		BpostShm.map,
			position:	new google.maps.LatLng( coords[0], coords[1] )
		});

		this.markers.push(marker);

		var LatLng = [];
		google.maps.event.addListener(marker, 'click', function() {
			LatLng.push(parseFloat(marker.position.lat().toFixed(4)));
			LatLng.push(parseFloat(marker.position.lng().toFixed(4)));

			var i, len;
			for (i = 0, len = BpostShm.points.coords.length; i < len; i++)
				if ($(BpostShm.points.coords[i]).not(LatLng).length == 0 && $(LatLng).not(BpostShm.points.coords[i]).length == 0)
					break;

			if (i < len)
				$( $('#poi li').get(i) ).trigger('click');
		});
	},
	removeMarkers: function() {
		var i;
		for (i = 0; i < this.markers.length; i++)
			this.markers[i].setMap(null);
		this.markers = [];
	},
	appendHours: function(response, $node)
	{
		var $hours 	= $('<div class="hours" />'),
			$table 	= $('<table />'),
			$button = $('<a />').addClass('button').text(BpostShm.lang['Next step']),
			$tr;

		if (response)
		{
			$.each(response, function(day, hours) {
				$tr = $('<tr />');

				if (!$.isEmptyObject(hours.am_open) && !$.isEmptyObject(hours.pm_open))
					$tr.append(
						'<td class="day">' + BpostShm.lang[day] + '</td><td>' + hours.am_open + (!$.isEmptyObject(hours.am_close) ? ' - ' + hours.am_close : '') + '</td>'
							+ '<td>' + hours.pm_open + (!$.isEmptyObject(hours.pm_close) ? ' - ' + hours.pm_close : '') + '</td>'
					);
				else if (!$.isEmptyObject(hours.am_open) && !$.isEmptyObject(hours.pm_close))
					$tr.addClass('two-cols').append(
						'<td class="day">' + BpostShm.lang[day] + '</td><td colspan="2">' + hours.am_open + ' - ' + hours.pm_close + '</td>'
					);
				else if ($.isEmptyObject(hours.pm_open) && $.isEmptyObject(hours.pm_close))
					$tr.addClass('two-cols').append(
						'<td class="day">' + BpostShm.lang[day] + '</td><td colspan="2">' + BpostShm.lang['Closed'] + '</td>'
					);
				else
					$tr.addClass('two-cols').append(
						'<td class="day">' + BpostShm.lang[day] + '</td><td colspan="2">' + hours.pm_open + (!$.isEmptyObject(hours.pm_close) ? ' - ' + hours.pm_close : '') + '</td>'
					);

				$table.append($tr);
			});

			$hours.append($table);
		}

		$hours.append($button).insertAfter( $node.find('a') );
		//$('#poi').scrollTo($node);
	},
	setDefaultStation: function(station_id)
	{
		if ('' != station_id)
		{
			elm = $("#poi li[data-servicepointid='"+station_id+"'] a");
			name_elm = $('span.name')[0];
			title_elm = elm.find(name_elm);
			$title = title_elm.text() + ' @';
			title_elm.text($title);
		
			setTimeout(function() {
				elm.trigger('click');
			}, 2000);
		}
			
	}
};