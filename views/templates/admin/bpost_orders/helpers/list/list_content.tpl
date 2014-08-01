{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
<tbody>
{if count($list)}
{foreach $list AS $index => $tr}
	<tr
	{if $position_identifier}id="tr_{$id_category}_{$tr.$identifier}_{if isset($tr.position['position'])}{$tr.position['position']}{else}0{/if}"{/if}
	class="{if $index is odd}alt_row{/if} {if $row_hover}row_hover{/if}"
	{if isset($tr.color) && $color_on_bg}style="background-color: {$tr.color}"{/if}
	>
		<td class="center">
			{if {$has_bulk_actions}}
				{if isset($list_skip_actions.delete)}
					{if !in_array($tr.$identifier, $list_skip_actions.delete)}
						<input type="checkbox" name="{$table|escape}Box[]" value="{$tr.$identifier|escape}" class="noborder" />
					{/if}
				{else}
					<input type="checkbox" name="{$table|escape}Box[]" value="{$tr.$identifier|escape}" class="noborder" />
				{/if}
			{/if}
		</td>
		{foreach $fields_display AS $key => $params}
			{block name="open_td"}
				<td
					{if isset($params.position)}
						id="td_{if !empty($id_category)}{$id_category}{else}0{/if}_{$tr.$identifier}"
					{/if}
					class="{if !$no_link}pointer{/if}
					{if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
					{if isset($params.class)} {$params.class}{/if}
					{if isset($params.align)} {$params.align}{/if}"
					{if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
						onclick="document.location = '{$current_index}&{$identifier}={$tr.$identifier}{if $view}&view{else}&update{/if}{$table}&token={$token}'">
					{else}
					>
				{/if}
			{/block}
			{block name="td_content"}
				{if isset($params.prefix)}{$params.prefix}{/if}
				{if isset($params.color) && isset($tr[$params.color])}
					<span class="color_field" style="background-color:{$tr[$params.color]};color:{if Tools::getBrightness($tr[$params.color]) < 128}white{else}#383838{/if}">
				{/if}
				{if isset($tr.$key)}
					{if isset($params.active)}
						{$tr.$key|strval}
					{elseif isset($params.activeVisu)}
						<img src="../img/admin/{if $tr.$key}enabled.gif{else}disabled.gif{/if}"
						alt="{if $tr.$key}{l s='Enabled' mod='bpostshm'}{else}{l s='Disabled' mod='bpostshm'}{/if}" title="{if $tr.$key}{l s='Enabled' mod='bpostshm'}{else}{l s='Disabled' mod='bpostshm'}{/if}" />
					{elseif isset($params.position)}
						{if $order_by == 'position' && $order_way != 'DESC'}
							<a href="{$tr.$key.position_url_down}" {if !($tr.$key.position != $positions[count($positions) - 1])}style="display: none;"{/if}>
								<img src="../img/admin/{if $order_way == 'ASC'}down{else}up{/if}.gif" alt="{l s='Down' mod='bpostshm'}" title="{l s='Down' mod='bpostshm'}" />
							</a>

							<a href="{$tr.$key.position_url_up}" {if !($tr.$key.position != $positions.0)}style="display: none;"{/if}>
								<img src="../img/admin/{if $order_way == 'ASC'}up{else}down{/if}.gif" alt="{l s='Up' mod='bpostshm'}" title="{l s='Up' mod='bpostshm'}" />
							</a>
						{else}
							{$tr.$key.position|intval + 1}
						{/if}
					{elseif isset($params.image)}
						{$tr.$key|strval}
					{elseif isset($params.icon)}
						{if is_array($tr[$key])}
							<img src="../img/admin/{$tr[$key]['src']|strval}" alt="{$tr[$key]['alt']|strval}" title="{$tr[$key]['alt']|strval}" />
						{/if}
					{elseif isset($params.price)}
						{$tr.$key|strval}
					{elseif isset($params.float)}
						{$tr.$key|strval}
					{elseif isset($params.type) && $params.type == 'date'}
						{$tr.$key|strval}
					{elseif isset($params.type) && $params.type == 'datetime'}
						{$tr.$key|strval}
					{elseif isset($params.type) && $params.type == 'decimal'}
						{$tr.$key|string_format:"%.2f"}
					{elseif isset($params.type) && $params.type == 'percent'}
						{$tr.$key|strval} {l s='%' mod='bpostshm'}
					{* If type is 'editable', an input is created *}
					{elseif isset($params.type) && $params.type == 'editable' && isset($tr.id)}
						<input type="text" name="{$key|strval}_{$tr.id|intval}" value="{$tr.$key|strval}" class="{$key|strval}" />
					{elseif isset($params.callback)}
						{$tr.$key|strval}
					{elseif $key == 'color'}
						<div style="float: left; width: 18px; height: 12px; border: 1px solid #996633; background-color: {$tr.$key|strval}; margin-right: 4px;"></div>
					{elseif isset($params.maxlength) && Tools::strlen($tr.$key) > $params.maxlength}
						<span title="{$tr.$key|strval}">{$tr.$key|truncate:$params.maxlength:'...'|escape:'htmlall':'UTF-8'}</span>
					{else}
						{$tr.$key|strval}
					{/if}
				{else}
					{block name="default_field"}--{/block}
				{/if}
				{if isset($params.suffix)}{$params.suffix}{/if}
				{if isset($params.color) && isset($tr.color)}
					</span>
				{/if}
			{/block}
			{block name="close_td"}
				</td>
			{/block}
		{/foreach}

	{if $shop_link_type}
		<td class="center" title="{$tr.shop_name|escape}">
			{if isset($tr.shop_short_name)}
				{$tr.shop_short_name|escape}
			{else}
				{$tr.shop_name|escape}
			{/if}</td>
	{/if}
	{if $has_actions}
		<td class="center" style="white-space: nowrap;">
			<select class="actions">
				<option value="">-</option>
				{foreach $actions AS $action}
					{if isset($tr.$action)}
						{$tr.$action|strval}
					{/if}
				{/foreach}
			</select>
		</td>
	{/if}
	</tr>
{/foreach}
{else}
	<tr><td class="center" colspan="{count($fields_display|intval) + 2}">{l s='No items found' mod='bpostshm'}</td></tr>
{/if}
</tbody>
