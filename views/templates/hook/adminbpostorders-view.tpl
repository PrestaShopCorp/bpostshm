{*
* 2014 Stigmi
*
* @author 		Stigmi.eu <www.stigmi.eu>
* @copyright 	2014 Stigmi
* @license 		http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="adminbpostorders-view">
	{if !empty($order)}
		<div class="label">
			<div class="bloc-command">
				<div class="metadata-command">
					<dl>
						<dt>{l s='Reference:' mod='bpostshm'}</dt>
						<dd>{$order->getReference()}</dd>
					</dl>
					<div class="actions">
						{if !empty($url_get_labels) && is_array($url_get_labels)}
							<ul>{strip}
								{foreach $url_get_labels as $url_get_label}
									<li>
										<a href="{$url_get_label|escape:'hexentity'}" class="print-labels" title="{l s='Print labels' mod='bpostshm'}" target="_blank">
											<img src="{$module_dir|escape}views/img/icons/printer.png" alt="{l s='Print labels' mod='bpostshm'}" />
											<span>{l s='Print labels' mod='bpostshm'}</span>
										</a>
									</li>
								{/foreach}
							{/strip}</ul>
						{/if}
					</div>
					<div class="clear"></div>
				</div>
			</div>
			{if !empty($boxes) && is_array($boxes)}
				{foreach $boxes as $box}
					<div class="box">
						<input name="id_box" type="hidden" value="{$box@iteration}" />

						<div class="box-title">
							<div class="metadata-command">
								<dl>
									<dd>
										{l s='Label' mod='bpostshm'} {$box@iteration}
									</dd>
								</dl>
								<dl>
									<dt>{l s='Status:' mod='bpostshm'}</dt>
									<dd>{$box->getStatus()}</dd>
								</dl>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				{/foreach}
			{/if}
		</div>
	{/if}
</div>