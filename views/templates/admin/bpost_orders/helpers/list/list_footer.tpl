{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

			</table>
			{if $bulk_actions}
				<p>
					{foreach $bulk_actions as $key => $params}
						<input type="submit" class="button" name="submitBulk{$key}{$table}" value="{$params.text}" {if isset($params.confirm)}onclick="return confirm('{$params.confirm}');"{/if} />
					{/foreach}
				</p>
			{/if}
		</td>
	</tr>
</table>
{if !$simple_header}
	<input type="hidden" name="token" value="{$token}" />
	</form>
{/if}

<script type="text/javascript">
	$(function() {
		/* Tabs */
		var $table 	= $('.order_label'),
			tr_list = [];
		$table.find('td.order_state').each(function(i, td) {
			var $td 	= $(td);

			if ($(td).text().trim() === '{$treated_status}')
			{
				tr_list.push($td.closest('tr'));
			}
		});

		if (tr_list.length)
		{
			var $table_treated = $table.clone();
			$table_treated.find('tbody').empty();
			$.each(tr_list, function(i, tr) {
				$table_treated.append(tr);
			});

			$('#adminbpostorders').prepend(
					$('<ul id="idTabs" />').append('<li><a href="#tab1">Open</a></li>', '<li><a href="#tab2">Treated</a></li>'),
					$('<div id="tab1"/>').append($table),
					$('<div id="tab2"/>').append($table_treated)
				)
				.children('ul').idTabs();
		}

		var $first_row = $table.find('tbody tr:eq(0)'),
			$first_row_haystack = $first_row.children('td'),
			$first_row_needle = $first_row.children('td.order_state'),
			position = $first_row_haystack.index($first_row_needle);

		$('tr, colgroup', '.order_label').each(function() {
			$(this).children(':eq('+position+')').remove();
		});

		if ('undefined' !== typeof location.hash && '#tab2' === location.hash)
			$('#idTabs').find('li:eq(1) a').trigger('click');
		/* /Tabs */

		$('select.actions')
			.on('change', function(e) {
				if (this.value)
				{
					if ('undefined' !== typeof $(this).children(':selected').data('target'))
						return window.open(this.value);

					$.get(this.value, { }, function(response) {
						if ('undefined' !== typeof response.errors && response.errors.length)
						{
							var errors = '';
							$.each(response.errors, function(i, error) {
								if (i > 0)
									errors += "\n";
								errors += error;
							});
							return alert(errors);
						}

						if ('undefined' !== typeof response.links)
						{
							$.each(response.links, function(i, link) {
								window.open(link);
							});
							location.reload();
							return;
						}

						if (response)
							location.reload();

					}, 'JSON');
				}
			})
			.children(':disabled').on('click', function() {
				var $option = $(this);
				if ($option.data('disabled'))
					alert($option.data('disabled'));
			});

		$('img.print').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $img = $(this);

			if ('undefined' !== typeof $img.data('labels'))
				$.get($img.data('labels'), { }, function(response) {
					if ('undefined' !== typeof response.links)
						$.each(response.links, function(i, link) {
							window.open(link);
						});
				});
		});
	});
</script>

{hook h='displayAdminListAfter'}
{if isset($name_controller)}
	{capture name=hookName assign=hookName}display{$name_controller|ucfirst}ListAfter{/capture}
	{hook h=$hookName}
{elseif isset($smarty.get.controller)}
	{capture name=hookName assign=hookName}display{$smarty.get.controller|ucfirst|htmlentities}ListAfter{/capture}
	{hook h=$hookName}
{/if}


{block name="after"}{/block}