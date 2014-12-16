{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if $version >= 1.5 && $version < 1.6}
				</table>
				{if $bulk_actions}
					<p id="bulky">
						{foreach $bulk_actions as $key => $params}
							<input type="submit" class="button" name="submitBulk{$key}{$table}" value="{$params.text}" {if isset($params.confirm)}onclick="return confirm('{$params.confirm}');"{/if} />
						{/foreach}
					</p>
				{/if}
			</td>
		</tr>
	</table>
	{if !$simple_header}
		<input type="hidden" name="token" value="{$token|escape}" />
		</form>
	{/if}

	{hook h='displayAdminListAfter'}
	{if isset($name_controller)}
		{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
		{hook h=$hookName}
	{elseif isset($smarty.get.controller)}
		{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
		{hook h=$hookName}
	{/if}

	{block name="after"}{/block}
{elseif $version >= 1.6}
		</table>
	</div>
	<div class="row">
		<div class="col-lg-6">
			{if $bulk_actions && $has_bulk_actions}
			<div class="btn-group bulk-actions">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					{l s='Bulk actions' mod='bpostshm'} <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li>
						<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id|escape}Box[]', true);return false;">
							<i class="icon-check-sign"></i>&nbsp;{l s='Select all' mod='bpostshm'}
						</a>
					</li>
					<li>
						<a href="#" onclick="javascript:checkDelBoxes($(this).closest('form').get(0), '{$list_id|escape}Box[]', false);return false;">
							<i class="icon-check-empty"></i>&nbsp;{l s='Unselect all' mod='bpostshm'}
						</a>
					</li>
					<li class="divider"></li>
					{foreach $bulk_actions as $key => $params}
						<li{if $params.text == 'divider'} class="divider"{/if}>
							{if $params.text != 'divider'}
							<a href="#" onclick="{if isset($params.confirm)}if (confirm('{$params.confirm}')){/if}sendBulkAction($(this).closest('form').get(0), 'submitBulk{$key}{$table}');">
								{if isset($params.icon)}<i class="{$params.icon}"></i>{/if}&nbsp;{$params.text}
							</a>
							{/if}
						</li>
					{/foreach}
				</ul>
			</div>
			{/if}
		</div>
		{if !$simple_header && $list_total > $pagination[0]}
		<div class="col-lg-6">
			{* Choose number of results per page *}
			<div class="pagination">
				{l s='Display' mod='bpostshm'}
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					{$selected_pagination|escape}
					<i class="icon-caret-down"></i>
				</button>
				<ul class="dropdown-menu">
				{foreach $pagination AS $value}
					<li>
						<a href="javascript:void(0);" class="pagination-items-page" data-items="{$value|intval}" data-list-id="{$list_id}">{$value}</a>
					</li>
				{/foreach}
				</ul>
				/ {$list_total|intval} {l s='result(s)' mod='bpostshm'}
				<input type="hidden" id="{$list_id}-pagination-items-page" name="{$list_id}_pagination" value="{$selected_pagination|intval}" />
			</div>
			<script type="text/javascript">
				$('.pagination-items-page').on('click',function(e){
					e.preventDefault();
					$('#'+$(this).data("list-id")+'-pagination-items-page').val($(this).data("items")).closest("form").submit();
				});
			</script>
			<ul class="pagination pull-right">
				<li {if $page <= 1}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="1" data-list-id="{$list_id|escape}">
						<i class="icon-double-angle-left"></i>
					</a>
				</li>
				<li {if $page <= 1}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$page|intval - 1}" data-list-id="{$list_id|escape}">
						<i class="icon-angle-left"></i>
					</a>
				</li>
				{assign p 0}
				{while $p++ < $total_pages}
					{if $p < $page-2}
						<li class="disabled">
							<a href="javascript:void(0);">&hellip;</a>
						</li>
						{assign var='p' value=$page-3}
					{elseif $p > $page+2}
						<li class="disabled">
							<a href="javascript:void(0);">&hellip;</a>
						</li>
						{assign p $total_pages}
					{else}
						<li {if $p == $page}class="active"{/if}>
							<a href="javascript:void(0);" class="pagination-link" data-page="{$p|intval}" data-list-id="{$list_id|escape}">{$p|intval}</a>
						</li>
					{/if}
				{/while}
				<li {if $page >= $total_pages}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$page|intval + 1}" data-list-id="{$list_id|escape}">
						<i class="icon-angle-right"></i>
					</a>
				</li>
				<li {if $page >= $total_pages}class="disabled"{/if}>
					<a href="javascript:void(0);" class="pagination-link" data-page="{$total_pages|intval}" data-list-id="{$list_id|escape}">
						<i class="icon-double-angle-right"></i>
					</a>
				</li>
			</ul>
			<script type="text/javascript">
				$('.pagination-link').on('click',function(e){
					e.preventDefault();

					if (!$(this).parent().hasClass('disabled'))
						$('#submitFilter'+$(this).data("list-id")).val($(this).data("page")).closest("form").submit();
				});
			</script>
		</div>
		{/if}
	</div>
	{block name="footer"}
	{foreach from=$toolbar_btn item=btn key=k}
		{if $k == 'back'}
			{assign 'back_button' $btn}
			{break}
		{/if}
	{/foreach}
	{if isset($back_button)}
	<div class="panel-footer">
		<a id="desc-{$table}-{if isset($back_button.imgclass)}{$back_button.imgclass}{else}{$k}{/if}" class="btn btn-default" {if isset($back_button.href)}href="{$back_button.href|escape:'html':'UTF-8'}"{/if} {if isset($back_button.target) && $back_button.target}target="_blank"{/if}{if isset($back_button.js) && $back_button.js}onclick="{$back_button.js}"{/if}>
			<i class="process-icon-back {if isset($back_button.class)}{$back_button.class}{/if}" ></i> <span {if isset($back_button.force_desc) && $back_button.force_desc == true } class="locked" {/if}>{$back_button.desc}</span>
		</a>
	</div>
	{/if}
	{/block}
	{if !$simple_header}
			<input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}" />
		</div>
	{else}
		</div>
	{/if}
	{block name="endForm"}
	</form>
	{/block}

	{hook h='displayAdminListAfter'}
	{if isset($name_controller)}
		{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
		{hook h=$hookName}
	{elseif isset($smarty.get.controller)}
		{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
		{hook h=$hookName}
	{/if}


	{block name="after"}{/block}
{/if}

{if $version >= 1.5}
	<script type="text/javascript">
		
		function reloadPage()
		{
			window.location.replace('{$reload_href}');
		}

	{literal}
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
	{/literal}
		
		// SRG end
		(function($) {
			$(function() {
				/* Tabs */
				var $table 	= $('table.order_bpost'),
					$thead  = $table.find('thead'),
					tr_list = [];

				$table.find('td.order_state').each(function(i, td) {
					var $td = $(td);
					if ($td.text().trim() == {$treated_status|intval})
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

					if ({$version|floatval} < 1.6)
					{
						$parent = $('#adminordersbpost');
						$parent.prepend(
							$('<ul id="idTabs" />').append(
									'<li><a href="#tab1">Open</a></li>',
									'<li><a href="#tab2">Treated</a></li>'
							),
							$('<div id="tab1"/>').append($table),
							$('<div id="tab2"/>').append($table_treated));

						$('#idTabs').idTabs()
							.find('a').on('click', function() {
								$thead.prependTo('.order_bpost:visible');
							});
					}
					else
					{
						$parent = $('.table-responsive');
						$parent.before(
							$('<ul id="idTabs" class="tab nav nav-tabs" />')
								.append(
									'<li class="tab-row active"><a href="#tab1">Open</a></li>',
									'<li class="tab-row"><a href="#tab2">Treated</a></li>'
							));
						$parent.prepend(
							$('<div id="tab1" />').append($table),
							$('<div id="tab2" style="display: none;" />').append($table_treated));
						$('#idTabs a').on('click', function(e) {
							var $link = $(this),
								$li = $link.parent();

							$li.addClass('active').siblings().removeClass('active');
							$parent.children().hide();
							$($link.attr('href')).show();
							// This was the 1.6 Treated header problem 
							$thead.prependTo('.order_bpost:visible');
						});
					}

					if ('undefined' !== typeof location.hash && '#tab2' === location.hash)
						$('#idTabs').find('li:eq(1) a').trigger('click');

				}

				// var $first_row = $table.find('tbody tr:eq(0)'),
				// 	$first_row_haystack = $first_row.children('td'),
				// 	$first_row_needle = $first_row.children('td.order_state'),
				// 	position = $first_row_haystack.index($first_row_needle);

				// $('tr, colgroup', 'table.order_bpost').each(function() {
				// 	$(this).children(':eq('+position+')').not('.list-empty').remove();
				// });
				/* /Tabs */

				/* Actions */
				$('select.actions')
					.on('change', function(e) {
						if (this.value)
						{
							if ('undefined' !== typeof $(this).children(':selected').data('target')) {
								// used for 'Open order'
								// if (fancy_pop)
								// 	srgBox.open(this.value, function () {
								// 		window.location.reload();
								// 		return;
								// 	});
								// else 
									window.open(this.value);
									//window.location.reload();
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
										else
											errors += error;
									});
									srgBox.displayError(errors, function() {
										//window.location.reload();
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
										//window.location.reload();
										reloadPage();
									});

									if (!fancy_pop)
										//window.location.reload();
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

				/* Bulk actions */
				if ({$version|floatval} < 1.6)
				{
					// Hide bulk actions unless needed
					$('p#bulky').toggle(false);
					var chkboxes = $('input[name="order_bpostBox[]"]');
					chkboxes.change(function() {
						some_checked = false;
						chkboxes.each(function() {
							some_checked = some_checked || this.checked;
						});

						$('p#bulky').toggle(some_checked);
					});

					$('input[name="checkme"]').change(function() {
						$('p#bulky').toggle(this.checked);
					});
					
				}
				//else
				//{
				//	1.6+ to do
				//}
				/* Bulk actions end */		

				$('img.print').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var $img = $(this);

					if ('undefined' !== typeof $img.data('labels'))
						$.get($img.data('labels'), { }, function(response) {
							if ('undefined' !== typeof response.links) {
								srgBox.reset();
								$.each(response.links, function(i, link) {
									if (fancy_pop)
										srgBox.addLink(link);
									else
										window.open(link);
								});
								srgBox.open();
								//window.location.reload();
								reloadPage();
								return;
							}
						});
				});

				// Print assigned labels
				{if !empty($labels) && is_array($labels)}
					var labels = {$labels|json_encode};
					srgBox.reset();
					$.each(labels, function(i, label) {
						$.each(label, function(j, link) {
							if (fancy_pop)
								srgBox.addLink(link);
							else
								window.open(link);
						});
						srgBox.open();
					});
				{elseif !empty($errors) && is_array($errors)}
					var errors = {$errors|json_encode};
					var err_msgs = '';
					$.each(errors, function(i, error) {
						err_msgs += "<li>" + error;
					});
					srgBox.displayError(err_msgs);
				{/if}

				$('#desc-order_bpost-new, .process-icon-new').attr('href', '{$url_get_label|urldecode}');
			});
		})(jQuery);
	</script>
{/if}