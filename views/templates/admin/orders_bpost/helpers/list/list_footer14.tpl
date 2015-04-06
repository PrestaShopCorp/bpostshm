<?php
/**
* list_footer jQuery implementation for Prestashop 1.4
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

?>
	<script type="text/javascript">
	
		var fancy_pop = false;
		srgBox = {
			_links: [],
			err_string: '<h2 style="color:red;">Error</h2>',
			reset: function () { while (this._links.length) { this._links.pop(); } },
			addLink: function (link) { this._links.push({href: link}); },
			_display: function (content, err, fnClosed) {
				if ('undefined' !== typeof(err)) content = this.err_string + content;
				if ('undefined' === typeof(fnClosed)) fnClosed = '';
				$.fancybox.open(content, {
					type: 'html',
					closeBtn: false,
					afterClose: fnClosed,
				});
			},
			display: function (content) { this._display(content); },
			displayError: function (msg, fnClosed) { this._display(msg, true, fnClosed); },
			open: function (url, fnClosed) {
				if ('undefined' === typeof(url) || '' === url) if (this._links.length) url = this._links; else return;
				if ('undefined' === typeof(fnClosed)) fnClosed = '';
				$.fancybox.open(url, {
					type: 'iframe',
					minWidth: '75%',
					minHeight: '75%',
					afterClose: fnClosed
				});
			},
		};

		function reloadPage()
		{
			//$visible_tab = $('#tab2').is(':visible') ? '#tab2' : '';
			$visible_tab = '';
			window.location.replace('<?php echo $reload_href; ?>' + $visible_tab);
		}
		
		(function($) {
			$(function() {
				
				/* Tabs */
				var $treated_status = <?php echo (int)$treated_status; ?>,
					$str_open = "<?php echo $str_tabs['open']; ?>",
					$str_treated = "<?php echo $str_tabs['treated']; ?>",
					$table = $('table.table'),
					$thead  = $table.find('thead'),
					tr_list = [];

				$class = $table.attr('class') + ' order_bpost';
				$table.attr('class', $class);
				$table.closest('td').attr('id', 'adminordersbpost');
				
				$table.find('td.order_state').each(function(i, td) {
					var $td = $(td);
					if ($td.text().trim() == $treated_status)
						tr_list.push($td.closest('tr'));
				});
	
				// remove order_state column;
				var $first_row = $table.find('tbody tr:eq(0)'),
					$first_row_haystack = $first_row.children('td'),
					$first_row_needle = $first_row.children('td.order_state'),
					position = $first_row_haystack.index($first_row_needle);

				$('tr, colgroup', 'table.order_bpost').each(function() {
					$(this).children(':eq('+position+')').not('.list-empty').remove();
				});

				// sep list
				if (tr_list.length)
				{
					var $table_treated = $table.clone(),
						$parent;

					$table_treated
						.find('thead').remove().end()
						.find('tbody').empty();

					$.each(tr_list, function(i, tr) {
						$table_treated.append(tr);
					});

					$parent = $('#adminordersbpost');
					$parent.prepend(
						$('<ul id="idTabs" />').append(
								'<li><a href="#tab1">' + $str_open + '</a></li>',
								'<li><a href="#tab2">' + $str_treated + '</a></li>'
						),
						$('<div id="tab1"/>').append($table),
						$('<div id="tab2"/>').append($table_treated));

					$('#idTabs').idTabs()
						.find('a').on('click', function() {
							$thead.prependTo('.order_bpost:visible');
						});
			

				// if ('undefined' !== typeof location.hash && '#tab2' === location.hash)
				// 	$('#idTabs').find('li:eq(1) a').trigger('click');

				}

				/* /Tabs */

				$('select.actions')
					.on('change', function(e) {
						if (this.value)
						{
							if ('undefined' !== typeof $(this).children(':selected').data('target')) {
								window.open(this.value);
								reloadPage();
								return;
							}

							$.get(this.value, { }, function(response) {
								if ('undefined' !== typeof response.errors && response.errors.length)
								{
									var errors = '';
									$.each(response.errors, function(i, error) {
										if (i > 0)
											errors += "<li>" + error;
										errors += error;
									});
									srgBox.displayError(errors, function() {
										reloadPage();
									});
									return;
									
								}

								if ('undefined' !== typeof response.links)
								{
									srgBox.reset();
									$.each(response.links, function(i, link) {
										if (fancy_pop)
											srgBox.addLink(link);
										else 
											window.open(link);

									});
									srgBox.open('', function () {
										reloadPage();
									});

									if (!fancy_pop)
										reloadPage();
									
									return;
								}
								
								if (response)
								{
									// if ($('#tab1').is(':visible') && location.href.substr(-5) === '#tab2')
									// {
									// 	window.location.href = location.href.substr(0, (location.href.length-5));
									// 	window.location.replace(location.href);
									// }
									// else if ($('#tab2').is(':visible') && location.href.substr(-5) !== '#tab2')
									// {
									// 	window.location.href += '#tab2';
									// 	window.location.replace(location.href);
									// }
									// else
										//window.location.reload();
										reloadPage();
									return;
								}

							}, 'JSON');
						}
					})
					.children(':disabled').on('click', function() {
						var $option = $(this);
						if ($option.data('disabled'))
							srgBox.display($option.data('disabled'));
							
					});

				$('img.print').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var $img = $(this);

					if ('undefined' !== typeof $img.data('labels'))
						$.get($img.data('labels'), { }, function(response) {
							if ('undefined' !== typeof response.errors && response.errors.length)
							{
								var errors = '';
								$.each(response.errors, function(i, error) {
									if (i > 0)
										errors += "<li>" + error;
									else
										errors += error;
								});
								srgBox.displayError(errors, function() {
									reloadPage();
								});
							}

							if ('undefined' !== typeof response.links) {
								srgBox.reset();
								$.each(response.links, function(i, link) {
									if (fancy_pop)
										srgBox.addLink(link);
									else
										window.open(link);
								});
								srgBox.open();
								reloadPage();
							}

							return;
						});
				});

				// if ('undefined' !== typeof location.hash && '#tab2' === location.hash)
				// 	$('#idTabs').find('li:eq(1) a').trigger('click');
				
			});
		})(jQuery);
	</script>
