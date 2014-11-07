{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="bpost-settings">
	<h2><img src="{$module_dir|escape}views/img/logo-carrier.jpg" alt="bpost" /> {l s='bpost Shipping manager' mod='bpostshm'}</h2>
	<br />
	{if !empty($errors)}
		{if $version >= 1.6}
			{if (!isset($disableDefaultErrorOutPut) || $disableDefaultErrorOutPut == false)}
				<div class="bootstrap">
					<div class="alert alert-danger">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						{if count($errors) > 1}
							{l s='%d errors' sprintf=$errors|count mod='bpostshm'}
							<br/>
						{/if}
						<ol>
							{foreach $errors as $error}
								<li>{$error|strval}</li>
							{/foreach}
						</ol>
					</div>
				</div>
			{/if}
		{else}
			<div class="error">
				<ul>{strip}
						{foreach $errors as $error}
							<li>{$error|escape}</li>
						{/foreach}
					{/strip}</ul>
			</div>
		{/if}
		<br />
	{/if}

	<fieldset class="panel">
		{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
			{l s='Description' mod='bpostshm'}
		{if $version < 1.6}</legend>{else}</div>{/if}
		<div class="panel-body">
			<p>{l s='bpost Shipping Manager is a service offered by bpost, allowing your customer to chose their preferred delivery method when ordering in your webshop.' mod='bpostshm'}</p>
			<p>{l s='The following delivery methods are currently supported:' mod='bpostshm'}</p>
			<ul>{strip}
				<li>{l s='Delivery at home or at the office' mod='bpostshm'}</li>
				<li>{l s='Delivery in a pick-up point or postal office' mod='bpostshm'}</li>
				<li>{l s='Delivery in a parcel locker' mod='bpostshm'}</li>
			{/strip}</ul>
			<p>{l s='When activated and correctly installed, this module also allows you to completely integrate the bpost administration into your webshop. This means that orders are automatically added to the bpost portal. Furthermore, if enabled, it is possible to generate your labels and tracking codes directly from the Prestashop order admin page.' mod='bpostshm'}
				<br />{l s='No more hassle and 100% transparent!' mod='bpostshm'}
			</p>
			<p>
				<a href="{l s='http://bpost.freshdesk.com/support/solutions/folders/208531' mod='bpostshm'}" title="{l s='Documentation' mod='bpostshm'}" target="_blank">
					<img src="{$module_dir|escape}views/img/icons/information.png" alt="{l s='Documentation' mod='bpostshm'}" />{l s='Documentation' mod='bpostshm'}
				</a>
			</p>
		</div>
	</fieldset>
	<br />
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel">
			{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
				{l s='Account settings' mod='bpostshm'}
			{if $version < 1.6}</legend>{else}</div>{/if}
			<div class="form-group">
				{if $version < 1.6}
				<div class="control-label{if $version < 1.6}-bw{/if} col-lg-3">
					<span class="label label-danger red">{l s='Important' mod='bpostshm'}</span>
				</div>
				{/if}
				<div class="margin-form col-lg-9{if $version >= 1.6} col-lg-offset-3{/if}">
					{if $version >= 1.6}<p><span class="label label-danger red">{l s='Important' mod='bpostshm'}</span></p>{/if}
					<p>
						{l s='You need a user account from bpost to use this module. Call 02 0201 11 11.' mod='bpostshm'}
						<!-- <br />
						{l s='Only here to test? Key in 999033 as account ID and DEMO_SHM as passphrase.' mod='bpostshm'} -->
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group">
				<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_id_account">{l s='Account ID' mod='bpostshm'}</label>
				<div class="margin-form col-lg-9">
					<input type="text" name="account_id_account" id="account_id_account" value="{$account_id_account|default:''}" size="50" />
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Your 6 digits bpost account ID used for the Shipping Manager' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group">
				<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_passphrase">{l s='Passphrase' mod='bpostshm'}</label>
				<div class="margin-form col-lg-9">
					<input type="text" name="account_passphrase" id="account_passphrase" value="{$account_passphrase|default:''}" size="50" />
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='The passphrase you entered in bpost Shipping Manager back-office application. This is not the password used to access bpost portal.' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group">
				<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_api_url">{l s='API URL' mod='bpostshm'}</label>
				<div class="margin-form col-lg-9">
					<input type="text" name="account_api_url" id="account_api_url" value="{$account_api_url|default:''}" size="50" />
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
					{if $version < 1.5}
						{l s='Do not modify this setting if you are not 100% sure of what you are doing' mod='bpostshm'}
					{else}	
						{l s='Do not modify this setting if you are not 100%% sure of what you are doing' mod='bpostshm'}
					{/if}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitAccountSettings">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>
	</form>
	<br />
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel">
			{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
				{l s='Delivery settings' mod='bpostshm'}
			{if $version < 1.6}</legend>{else}</div>{/if}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Home delivery only' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="display_home_delivery_only" id="home_delivery_only_1" value="1"{if !empty($display_home_delivery_only)} checked="checked"{/if} />
						<label for="home_delivery_only_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="display_home_delivery_only" id="home_delivery_only_0" value="0"{if empty($display_home_delivery_only)} checked="checked"{/if} />
						<label for="home_delivery_only_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, only the home delivery option is enabled. all other delivery methods (pick-up points and parcel locker) are disabled.' mod='bpostshm'}
					</p>
				</div>

				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='International delivery' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<p class="radio">
						<label for="international_delivery_0">
							<input type="radio" name="display_international_delivery" id="international_delivery_0" value="0"{if empty($display_international_delivery)} checked="checked"{/if} />
							{l s='World Express Pro' mod='bpostshm'}
						</label>
						<br />
						<label for="international_delivery_1">
							<input type="radio" name="display_international_delivery" id="international_delivery_1" value="1"{if !empty($display_international_delivery)} checked="checked"{/if} />
							{l s='World Business' mod='bpostshm'}
						</label>
					</p>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Choose international delivery option.' mod='bpostshm'}
					</p>
				</div>

			</div>
			<div class="clear"></div>
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitDeliverySettings">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>
	</form>
	<br />
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel">
			{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
				{l s='Delivery options' mod='bpostshm'}
			{if $version < 1.6}</legend>{else}</div>{/if}
			<div id="delivery-options" class="form-group">
			<!-- content start -->
			<input type="hidden" name="delivery_options_list" value="">
			{$delivery_sections = array()}
			{foreach $delivery_options as $name => $options}
				{$delivery_sections[] = "$name"}
				<div id="section-{$name}">
					<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s=$options['title'] mod='bpostshm'}</span>
					<div class="margin-form col-lg-9">
						<p class="checkbox">
						{foreach $options['full'] as $key => $opt}
							{assign var="chk_id" value="$name-$key"}
							<label for="{$chk_id}">
								<input type="checkbox" name="{$chk_id}" id="{$chk_id}" value="{$key}" {if in_array($key, $options['list'])}checked="checked"{/if} />&nbsp;{$opt['title']}
							</label>
							<br />
						{/foreach}	
						</p>
					</div>
					<div class="margin-form col-lg-9 col-lg-offset-3">
						<p class="preference_description help-block">
						{foreach $options['full'] as $key => $opt}
							<b>{$opt['title']}</b>: {$opt['info']}
							<br />
						{/foreach}
						</p>
					</div>
				</div>	 		
			{/foreach}
			<!-- content end -->
			</div>
			<div class="clear"></div>
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitDeliveryOptions">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>	
	</form>
	<br />
	{if !empty($account_id_account) && !empty($account_passphrase)}
		<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
			<fieldset class="panel">
				{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
					{l s='Country settings' mod='bpostshm'}
				{if $version < 1.6}</legend>{else}</div>{/if}
				<div class="form-group">
					<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Zone configuration' mod='bpostshm'}</span>
					<div class="margin-form col-lg-9">
						<p class="radio">
							<label for="country_international_orders_1">
								<input type="radio" name="country_international_orders" id="country_international_orders_1" value="1"
									{if empty($country_international_orders) || 1 == $country_international_orders} checked="checked"{/if} />
								{l s='Configure carrier prices using existing PrestaShop zones' mod='bpostshm'}
							</label>
						</p>
						<p class="radio">
							<label for="country_international_orders_2">
								<input type="radio" name="country_international_orders" id="country_international_orders_2" value="2"
									{if !empty($country_international_orders) && 2 == $country_international_orders} checked="checked"{/if} />
								{l s='Configure carrier prices by country, creating new zones' mod='bpostshm'}
							</label>
						</p>
					</div>
					<div class="margin-form col-lg-9 col-lg-offset-3">
						<p class="preference_description help-block">
							<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033754" title="{l s='Click here' mod='bpostshm'}" target="_blank">
							<!-- <a class="info-link" href="#desc-zone-config" title="{l s='Click here' mod='bpostshm'}"> -->
							{l s='Click here' mod='bpostshm'}</a> {l s='to see how this list is created' mod='bpostshm'}
						</p>
						<p class="preference_description help-block" id="desc-zone-config" style="display:none;">
							{l s='IMPORTANT: description zone-config' mod='bpostshm'}
						</p>
					</div>
				</div>
				<div class="clear"></div>
				<div class="form-group">
				<table class="select-multiple{if empty($country_international_orders) || 1 == $country_international_orders} hidden{/if}">
					<tbody>
						<tr>
							<th width="45%"><i>{l s='Countries activated in the Shipping Manager' mod='bpostshm'}</i></th>
							<th></th>
							<th width="45%"><i>{l s='Zones to create' mod='bpostshm'}</i></th>
						</tr>
						<tr>
							<td>
								<select multiple="multiple" id="country-list">
								{foreach $product_countries as $iso_code => $_country}
									<option value="{$iso_code|escape}">{$_country|escape}</option>
								{/foreach}
								</select>
							</td>
							<td width="50" align="center">
								<img id="add_country" src="{$module_dir|escape}views/img/icons/arrow-right.png" alt="{l s='Add' mod='bpostshm'}" />
								<br />
								<img id="remove_country" src="{$module_dir|escape}views/img/icons/arrow-left.png" alt="{l s='Remove' mod='bpostshm'}" />
							</td>
							<td>
								<select name="enabled_country_list[]" multiple="multiple" id="enabled-country-list">
								{foreach $enabled_countries as $iso_code => $_country}
									<option value="{$iso_code|escape}">{$_country|escape}</option>
								{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="3">
								<img id="get_countries" src="{$module_dir|escape}views/img/ajax-refresh.gif" alt="{l s='Refresh' mod='bpostshm'}" />
								&nbsp;&nbsp;{l s='Refresh left list' mod='bpostshm'}
								<br><span id="tracie"></span>
							</td>
						</tr>
					</tbody>
				</table>
				</div>
				<br />
				<div class="margin-form panel-footer">
					<button class="button btn btn-default pull-right" type="submit" name="submitCountrySettings">
						<i class="process-icon-save"></i>
						{l s='Save settings' mod='bpostshm'}
					</button>
				</div>
			</fieldset>
		</form>
		<br />
	{/if}
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel">
			{if $version < 1.6}<legend><img src="{$module_dir|escape}views/img/icons/bpost.png" alt="bpost" />{else}<div class="panel-heading">{/if}
				{l s='Label settings' mod='bpostshm'}
			{if $version < 1.6}</legend>{else}</div>{/if}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Use PrestaShop to manage labels' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="label_use_ps_labels" id="label_use_ps_labels_1" value="1"{if !empty($label_use_ps_labels)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_use_ps_labels_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="label_use_ps_labels" id="label_use_ps_labels_0" value="0"{if empty($label_use_ps_labels)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_use_ps_labels_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, labels are generated directly within PrestaShop. It is not needed to use the bpost Shipping Manager for these tasks.' mod='bpostshm'}
						<br />
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033755" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						<!-- <a class="info-link" href="#desc-use-labels" title="{l s='Click here' mod='bpostshm'}">{l s='Click here' mod='bpostshm'}</a>  -->
						{l s='Click here' mod='bpostshm'}</a> 
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-use-labels" style="display:none;">
						{l s='IMPORTANT: description use-labels' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Label format' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<p class="radio">
						
						<label for="label_pdf_format_A4">
							<input type="radio" name="label_pdf_format" id="label_pdf_format_A4" value="A4"{if empty($label_pdf_format) || 'A4' == $label_pdf_format} checked="checked"{/if} />
							{l s='Default format A4 (PDF)' mod='bpostshm'}
						</label>
					</p>
					<p class="radio">
						
						<label for="label_pdf_format_A6">
							<input type="radio" name="label_pdf_format" id="label_pdf_format_A6" value="A6"{if !empty($label_pdf_format) && 'A6' == $label_pdf_format} checked="checked"{/if} />
							{l s='Default format A6 (PDF)' mod='bpostshm'}
						</label>
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<!-- <div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Retour label' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="label_retour_label" id="label_retour_label_1" value="1"{if !empty($label_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_retour_label_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="label_retour_label" id="label_retour_label_0" value="0"{if empty($label_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_retour_label_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, a retour label is automatically added and printed when generating labels. If disabled, you are able to manually create retour labels.' mod='bpostshm'}
						<a class="info-link" href="#desc-retour-label" title="{l s='Click here' mod='bpostshm'}">{l s='Click here' mod='bpostshm'}</a> {l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-retour-label" style="display:none;">
						{l s='IMPORTANT: description retour-label' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div> -->
			<div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Track & Trace integration' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="label_tt_integration" id="label_tt_integration_1" value="1"{if !empty($label_tt_integration)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_tt_integration_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="label_tt_integration" id="label_tt_integration_0" value="0"{if empty($label_tt_integration)} checked="checked"{/if} />
						<label class="col-lg-3" for="label_tt_integration_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, an email containing Track & Trace information is automatically sent to customers when generating labels.' mod='bpostshm'}
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033757" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						<!-- <a class="info-link" href="#desc-tt-email" title="{l s='Click here' mod='bpostshm'}"> -->
						{l s='Click here' mod='bpostshm'}</a> 
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-tt-email" style="display:none;">
						{l s='IMPORTANT: description tt-email' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Other settings' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<!-- {l s='Update T&T status of treated orders every' mod='bpostshm'}
					<select name="label_tt_frequency" class="fixed-width-xs">
						{for $i=1 to 4 nocache}
							<option value="{$i|escape}"{if !empty($label_tt_frequency) && $i == $label_tt_frequency} selected="selected"{/if}>{$i|escape}&nbsp;</option>
						{/for}
					</select> {l s='hour(s)' mod='bpostshm'}
					<div class="clear"></div> -->
					<p class="checkbox">
						<label for="label_tt_update_on_open">
							<input type="checkbox" name="label_tt_update_on_open" id="label_tt_update_on_open" style="margin-right:2px;" 
							value="1"{if !empty($label_tt_update_on_open)} checked="checked"{/if} />
							{l s='Update T&T status of treated orders automatically when opening orders.' mod='bpostshm'}
						</label>
					</p>
				</div>
			</div>
			<br />
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitLabelSettings">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>
	</form>

	<script type="text/javascript">
	$(function() {
		$('.info-link').live('click', function(e) {
			e.preventDefault();
			
			var targetDescription = $($(this).attr('href'));
			targetDescription.toggle();
		});

		$('#country-list, #enabled-country-list').live('focus', function() {
			var $select = $(this),
				$handler = $select.is('#country-list') ? $('#add_country') : $('#remove_country');

			if ($select.children(':selected').length) {
				$handler.css('opacity', 1);
			}
		}).live('blur', function() {
			var $select = $(this),
				$handler = $select.is('#country-list') ? $('#add_country') : $('#remove_country');

			if (!$select.children(':selected').length) {
				$handler.css('opacity', .3);
			}
		});

		$('#add_country').live('click', function() {
			var $countryList = $('#country-list'),
				$enabledCountryList = $('#enabled-country-list'),
				$countries = $countryList.children(':selected'),
				enabledCountries = [];

			if (!$countries.length) {
				return;
			}

			$.each($enabledCountryList.children(), function() {
				enabledCountries.push(this.value);
			});

			$.each($countries, function() {
				if ($.inArray(this.value, enabledCountries) < 0) {
					$enabledCountryList.append( $(this).clone(true) );
				}
			});
		});

		$('#remove_country').live('click', function() {
			$('#enabled-country-list').children(':selected').remove();
		});

		var $intList = $('.select-multiple'),
			$imgRef = $('#get_countries'),
			$imgSrc = $imgRef.attr('src');
		$imgRef.live('click', function() {
			getEnabledCountries();
		});

		function trace(str) { $('span#tracie').html(str); }
		function refreshingList(bState) {
			$imgRef.attr('src', $imgSrc.replace(bState? '.gif':'-load.gif', bState? '-load.gif':'.gif'));
		}

		function getEnabledCountries() {

			trace('');
			refreshingList(true);
			
			$.ajax({
			    type: "GET",
			    url: "{$url_get_available_countries|escape:'javascript'}",
			    data: {},
			    contentType: "application/json; charset=utf-8",
			    dataType: "json"
			})
			   	.success( function(data) {
			   		if (data.Error)
			        	trace(data.Error);
			        else {
			        	$options = '';
						$.each(data, function (key, value) {
		        			$options += '<option value="'+key+'">'+value+'</option>';
		        		});
						
						if ($options.length)
							$('#country-list').html($options);
			        }
			        refreshingList(false);
			    })
			    .error( function(error_msg){
			        trace("{l s='Unable to retrieve the list. Please try again later.' mod='bpostshm'}");
			    	refreshingList(false);
			    });

		}

		$('input[name="country_international_orders"]').live('change', function() {
			$intList.toggleClass('hidden');
		});

		// form select elements are disfunctional so mimic the result
		$('button[name="submitCountrySettings"]').live('click', function(e) {
			// e.preventDefault();
			eclist = $('#enabled-country-list').children();
			if( eclist.length )
				$.each(eclist, function() {
					this.selected = true;
				});
			else
				$('#enabled-country-list').html('<option selected value="REMOVE">(empty)</option>');
				//eclist.remove().end().append('<option selected value="REMOVE">(empty)</option>') ;
		});

		// Delivery options submit
		var del_sections = {$delivery_sections|@json_encode};
		$('button[name="submitDeliveryOptions"]').live('click', function(e) {
			//e.preventDefault();
{literal}
			delopts = {};
			$.each(del_sections, function() {
				checked_opts = '';
				$('#section-' + this).find('input:checkbox').each(function() {
					if (this.checked)
						checked_opts += '|' + this.value;
				});
				if ('' !== checked_opts)
					checked_opts = checked_opts.substr(1);
					//$('input[name="delopt_list_'+this+'"]').val(checked_opts.substr(1));
				delopts['__'+this] = checked_opts;
			});
			json_options = JSON.stringify(delopts).replace(/__/g, '');
			//$.fancybox.open(json_options, {});
			$('input[name="delivery_options_list"]').val(json_options);
{/literal}
		});
		
		// special section-home: if 350-insurance is checked 300-signature is unchecked & disabled
		$('#home-350').change(function() {
			elm = $('input[name="home-300"]');
			if (this.checked && elm.prop('checked'))
				elm.prop('checked', false);

			elm.prop('disabled', this.checked);
		});
		// and at page load
		if ($('#home-350').prop('checked')) {
			$('input[name="home-300"]').prop('disabled', true);
		}
		//
		$('input[name="label_use_ps_labels"]').live('change', function() {
			$(this).closest('.form-group').nextAll('.form-group').toggleClass('hidden');
		});

	});
	</script>
</div>