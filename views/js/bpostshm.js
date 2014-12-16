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

//<![CDATA[
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
	default_station_id: '',
	is_busy:			false,
	manual_search: 		false, 
	init: function(points, shipping_method, default_station_id)
	{
		//this.points 			= points;
		this.shipping_method 	= shipping_method;
		this.default_station_id = default_station_id;

		var mapParams = {
			mapTypeId:	google.maps.MapTypeId.ROADMAP,
			zoom:		12
		};

		$has_points = points.list && points.list.length && points.coords.length;
		if ($has_points)
			mapParams.center = new google.maps.LatLng( points.coords[0][0], points.coords[0][1] );

		this.map = new google.maps.Map(document.getElementById('map-canvas'), mapParams);
		this.geocoder = new google.maps.Geocoder();

		this.bindEventListeners();

		if ($has_points)
			this.update(points);
	},
	update: function(points)
	{
		if ('undefined' !== typeof points)
			this.points = points;

		if (!this.points.list.length || !this.points.coords.length)
			//return $('#searchSubmit').after( $('<span class="error">' + this.lang['No results found'] + '</span>') );
			return trace(this.lang['No results found']);

		this.updatePointList();
		this.updateGMapMarkers();
//
		var bounds = new google.maps.LatLngBounds();
		$.each(this.points.coords, function(i, coord) {
			latLng = new google.maps.LatLng(coord[0], coord[1]);
			bounds.extend(latLng);
		});

		_map = this.map;
		_map.panToBounds(bounds);
		if (this.points.coords.length > 1)	
			setTimeout(function() {
				_map.fitBounds(bounds);
			}, 600);
//
		// if (this.shipping_method < 4)
		// 	this.map.panTo( new google.maps.LatLng(this.points.coords[0][0], this.points.coords[0][1]) );
		
		return;
	},
	bindEventListeners: function()
	{
		$('#search-form').live('submit', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var postcode	= $('#postcode').val(),
				city		= $('#city').val();

			$is_manual_search = BpostShm.manual_search;
			BpostShm.manual_search = false;
			trace('');

			if ('undefined' !== typeof BpostShm.cache.nearest_service_points[postcode])
				return BpostShm.update(BpostShm.cache.nearest_service_points[postcode]);

			$.get(BpostShm.services.get_nearest_service_points, {
				postcode:	postcode,
				city:		city
			}, function(response) {
				if ('undefined' !== typeof response.coords)
				{
					BpostShm.cache.nearest_service_points[postcode] = response;
					BpostShm.update(response);
				}
				else
					if ($is_manual_search)
						trace(BpostShm.lang['No results found']);

			});
		});

		var $searchInputs = $('#postcode, #city');
		$searchInputs.live('keydown', function() {
			BpostShm.manual_search = true;
			$searchInputs.filter(':not(#' + this.id + ')').val('');
		});

		$('#poi li .button')
			.live('click', function() {
				var $poi = $(this).closest('li');

				$.post(BpostShm.services.set_service_point, {
					service_point_id:	$poi.data('servicepointid'),
					sp_type:			$poi.data('sptype')
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
								// passthrough:	true,
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
			if ($(e.target).is('.button') || BpostShm.is_busy)
				return;
			
			if ($(e.target).is('a'))
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
							service_point_id: 	$poi.data('servicepointid'),
							sp_type:  			$poi.data('sptype')
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
// GoogleMaps Events
		google.maps.event.addListener(this.map, 'tilesloaded', function()
		{
			var default_station_id = BpostShm.default_station_id;

			if ('' !== default_station_id)
			{
				BpostShm.default_station_id = '';
				elm = $("#poi li[data-servicepointid='"+default_station_id+"'] a");
				name_elm = $('span.name')[0];
				title_elm = elm.find(name_elm);
				$title = title_elm.text() + ' @';
				title_elm.text($title);

				setTimeout(function() {
					elm.trigger('click');
				}, 500);
			}
		});

		google.maps.event.addListener(this.map, 'dragstart', function()
		{
			var map = BpostShm.map;
			BpostShm.start_center = map.getCenter();
		});
		
		google.maps.event.addListener(this.map, 'dragend', function()
		{
			var map = BpostShm.map,
				geocoder = BpostShm.geocoder,
				latlng = map.getCenter();
			
			geocoder.geocode({'latLng': latlng}, function(results, status) {
				// debugger;
				if (status == google.maps.GeocoderStatus.OK) {
      				post_code = '';
      				city = '';
      				not_belgium = true;
      				no_postcode = true;
      				$.each(results, function(i, result) {
      					$.each(result.address_components, function(ia, address_part) {
      						// if ('administrative_area_level_1' === address_part.types[0])
	      					// 		city = address_part.short_name;

	      					if (not_belgium)
      							not_belgium = ('BE' !== address_part.short_name);
      						else {
	      						if ('postal_code' === address_part.types[0]) {
	      							post_code = address_part.short_name;
	      							no_postcode = ('' === post_code);
	      						}
	      					}
      						
      						return no_postcode;
      					});

      					return not_belgium;
      				});

      				if (!no_postcode) {
      					$('#city').val(city);
      					$('#postcode').val(post_code);
      					$('input[name=searchSubmit]').trigger('click');
      					// trace(post_code);
      				}
      			}
			});
			// trace('drag ended');
		});
// 
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
					.data('sptype', row.type)
					.attr('data-sptype', row.type)
					.find('a')
						.attr('title', row.office)
						// Bind marker animation
						.click(function() {
							toggleBounce(i);
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

			$.each(this.points.coords, function(i, coord) {
				setTimeout(function() {
					BpostShm.addMarker(coord);
				}, i * 50);
			});
		}
	},
	addMarker: function(coord) {
		var marker = new google.maps.Marker({
			animation:	google.maps.Animation.DROP,
			icon:		this.icon,
			map:		BpostShm.map,
			position:	new google.maps.LatLng( coord[0], coord[1] )
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
			{
				$( $('#poi li').get(i) ).trigger('click');
				toggleBounce(i);
			}
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
	mapDistance: function(new_center)
	{
		if (0 == this.start_center.length)
			return 0;

		var map = BpostShm.map,
			proj = map.getProjection(),
			scale = map.getZoom();
			//scale = Math.pow(2,map.getZoom());
		
		point_prev = proj.fromLatLngToPoint(BpostShm.start_center);
		point_now = proj.fromLatLngToPoint(new_center);

		/*
		dist = Math.sqrt((
				Math.pow(2, (point_now.x - point_prev.x) * scale) + 
				Math.pow(2, (point_now.x - point_prev.x) * scale)
				) / scale);
		*/
		dist = Math.sqrt(
				Math.pow(2, (point_now.x - point_prev.x)) + 
				Math.pow(2, (point_now.y - point_prev.y))
				);

		return dist; //Math.abs((dist - 1.4) * 10000); // * scale;
	},
	distanceMoved: function(new_center)
	{
		if (0 == this.start_center.length)
			return 0.0;
		var lat1 = this.start_center.lat(),
			lng1 = this.start_center.lng(),
			lat2 = new_center.lat(),
			lng2 = new_center.lng();

		var theta1 = toRadians(lat1),
			theta2 = toRadians(lat2),
			delta_theta_by2 = toRadians(lat2-lat1)/2,
			delta_lambda_by2 = toRadians(lng2-lng1)/2;

		var R = 6371, // km
			a = Math.sin(delta_theta_by2) * Math.sin(delta_theta_by2) +
		        Math.cos(theta1) * Math.cos(theta2) *
		        Math.sin(delta_lambda_by2) * Math.sin(delta_lambda_by2);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

		return R * c;
	},
	// setDefaultStation: function(station_id)
	// {
	// 	if ('' != station_id)
	// 	{
	// 		elm = $("#poi li[data-servicepointid='"+station_id+"'] a");
	// 		name_elm = $('span.name')[0];
	// 		title_elm = elm.find(name_elm);
	// 		$title = title_elm.text() + ' @';
	// 		title_elm.text($title);
		
	// 		elm.trigger('click');
	// 		// setTimeout(function() {
	// 		// 	elm.trigger('click');
	// 		// }, 2000);
	// 	}		
	// }
};

function toRadians(num)
{
	return !isNaN(num) ? num * Math.PI / 180 : 0.0;
}

function toggleBounce(i)
{
 	var marker 		= BpostShm.markers[i],
		animation 	= google.maps.Animation.BOUNCE;

	if ('undefined' !== typeof marker && null !== marker.getAnimation())
		animation = null;

	$(BpostShm.markers).each(function() {
		this.setAnimation(null);
	});
	marker.setAnimation(animation);
}

function trace($msg)
{
	if ('undefined' === $msg) $msg = 'Ready';
	
	if ($('#tracie').length === 0)
		$('#search-form').append('<span id="tracie" style="color: silver;margin-left: 5px;"></span>');
	
	return $('#tracie').text($msg);
}
//]]>