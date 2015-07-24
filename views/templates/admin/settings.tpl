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
								<li>{$error|escape}</li>
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
	{if $version < 1.6}<legend class="tab-wrapper">{/if}
	<ul class="bpost-tabs">
		<!-- <li>
			<a href="#fs-description">
				{l s='Description' mod='bpostshm'}
			</a>
		</li> -->
		<li>
			<a href="#fs-account">
				{l s='Account settings' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-delopts">
				{l s='Delivery options' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-delivery-set">
				{l s='Delivery settings' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-intl-set">
				{l s='International settings' mod='bpostshm'}
			</a>
		</li>
		<li>
			<a href="#fs-label-set">
				{l s='Label settings' mod='bpostshm'}
			</a>
		</li>
	</ul>
	{if $version < 1.6}</legend>{/if}
	<!-- <fieldset class="panel" id="fs-description">
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
	</fieldset> -->
	<!-- Account settings -->
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel" id="fs-account">
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
			<br>
			<div class="form-group">
				{if $version < 1.6}
				<div class="control-label{if $version < 1.6}-bw{/if} col-lg-3">
					<span class="label label-danger red">{l s='Important' mod='bpostshm'}</span>
				</div>
				{/if}
				<div class="margin-form col-lg-9{if $version >= 1.6} col-lg-offset-3{/if}">
					{if $version >= 1.6}<p><span class="label label-danger red">{l s='Important' mod='bpostshm'}</span></p>{/if}
					<p>
						{l s='You need a user account from bpost to use this module. Call 02/201 11 11.' mod='bpostshm'}
						<br />
						<a href="https://www.bpost.be/portal/goHome" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						{l s='Click here' mod='bpostshm'}</a> 
						{l s='to connect to your bpost account' mod='bpostshm'}.
					</p>
				</div>
			</div>
			<div class="clear"></div>
			<div class="form-group">
				<label class="control-label{if $version < 1.6}-bw{/if} col-lg-3" for="account_id_account">{l s='Account ID' mod='bpostshm'}</label>
				<div class="margin-form col-lg-9">
					<input type="text" name="account_id_account" id="account_id_account" value="{$account_id_account|escape}" size="50" />
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
					<input type="text" name="account_passphrase" id="account_passphrase" value="{$account_passphrase|escape}" size="50" />
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
					<input type="text" name="account_api_url" id="account_api_url" value="{$account_api_url|escape}" size="50" />
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
	
	<!-- new Delivery options -->
	<div class="del-opt-tpl" style="display:none;">
		<span class="dex"> from </span>
		<input type="text" class="currency">
		<img src="{$module_dir|escape}views/img/icons/information.png" style="margin:2px 6px;">
	</div>
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel" id="fs-delopts">
			<div id="delivery-options" class="form-group">
			<!-- content start -->
			<input type="hidden" name="delivery_options_list" value="">
			<!-- <ul class="delopt-tabs">
			{foreach $delivery_options as $dm => $options}
				<li class="delopt-tab-row"><a href="#dm-{$dm|intval}">{l s=$options['title'] mod='bpostshm'}</a></li> 
			{/foreach}
			</ul> --> 
			{foreach $delivery_options as $dm => $options}
			{assign var="is_intl" value=(9 === $dm)}
				<div id="dm-{$dm|intval}">
					<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s=$options['title'] mod='bpostshm'}</span>
					<div class="margin-form col-lg-9">
					<table>
					{foreach $options['opts'] as $key => $opt}
					{assign var="opt_id" value="$dm-$key"}
						<tr>
							<td class="checkbox">
								<label {if $version >= 1.6}style="margin-right:5px;"{/if}>
									<input type="checkbox" class="del-opt" data-id="{$opt_id|escape}" from="{$opt['from']|escape}"{if isset($opt['cost'])} cost="{$opt['cost']|escape}"{/if} {if $opt['checked']}checked="checked"{/if} />
									&nbsp;{$delivery_options_info[$key]['title']|escape}
								</label>
							</td>
						</tr>
					{/foreach}	
					</table>
					{if $is_intl}
						<!-- <p class="radio">
							<label for="international_delivery_0">
								<input type="radio" name="display_international_delivery" id="international_delivery_0" value="0"{if empty($display_international_delivery)} checked="checked"{/if} />
								{l s='World Express Pro' mod='bpostshm'}
							</label>
							<br />
							<label for="international_delivery_1">
								<input type="radio" name="display_international_delivery" id="international_delivery_1" value="1"{if !empty($display_international_delivery)} checked="checked"{/if} />
								{l s='World Business' mod='bpostshm'}
							</label>
						</p> -->
					{/if}
					</div>

					{if $is_intl}
						<!-- <div class="margin-form col-lg-9 col-lg-offset-3">
							<p class="preference_description help-block">
								{l s='Choose international delivery option.' mod='bpostshm'}
							</p>
						</div> -->
					{/if}
					<div class="margin-form col-lg-9 col-lg-offset-3">
						<p class="preference_description help-block">
						{foreach $options['opts'] as $key => $opt}
							<b>{$delivery_options_info[$key]['title']|escape}</b>: {$delivery_options_info[$key]['info']|escape}
							<br />
						{/foreach}
						</p>
					</div>
				</div>	 		
			{/foreach}
			<!-- content end -->
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						<br />
						{l s='Please note the following' mod='bpostshm'}&nbsp;
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000036819-configuring-delivery-options" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						<!-- <a class="info-link" href="#desc-del-options" title="{l s='Click here' mod='bpostshm'}"> -->
						{l s='important information' mod='bpostshm'}</a>.
					</p>
					<!-- <p class="preference_description help-block" id="desc-del-options" style="display:none;">
						{l s='IMPORTANT: description del-options' mod='bpostshm'}
					</p> -->
				</div>
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
	
	<!-- Delivery settings -->
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel" id="fs-delivery-set">
		{if isset($display_delivery_date)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Display delivery date' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="display_delivery_date" id="display_delivery_date_1" value="1"{if !empty($display_delivery_date)} checked="checked"{/if} />
						<label for="display_delivery_date_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="display_delivery_date" id="display_delivery_date_0" value="0"{if empty($display_delivery_date)} checked="checked"{/if} />
						<label for="display_delivery_date_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Option to display the expected delivery date to the client (Belgium only).' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
		{/if}
		{if isset($ship_delay_days)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Days between order and shipment' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<input type="text" name="ship_delay_days" id="ship-delay" value="{$ship_delay_days|intval}">
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Default is 0 (next day delivery), maximum 8' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
		{/if}
		{if isset($cutoff_time)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Next day delivery allowed until' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<input type="text" name="cutoff_time" id="cutoff-time" value="{$cutoff_time|escape}">&nbsp;h
				</div>
				<!-- <div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Next day delivery allowed until' mod='bpostshm'}
					</p>
				</div> -->
			</div>
			<div class="clear"></div>
		{/if}
		{if isset($hide_date_oos)}
			<div class="form-group">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Hide delivery date when out of stock' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="hide_date_oos" id="hide_date_oos_1" value="1"{if !empty($hide_date_oos)} checked="checked"{/if} />
						<label for="hide_date_oos_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="hide_date_oos" id="hide_date_oos_0" value="0"{if empty($hide_date_oos)} checked="checked"{/if} />
						<label for="hide_date_oos_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='Do not display delivery date when at least one product in the cart, is out of stock.' mod='bpostshm'}
					</p>
				</div>
			</div>
			<div class="clear"></div>
		{/if}
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitDeliverySettings">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>
	</form>
	<!-- Label settings -->
	<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
		<fieldset class="panel" id="fs-label-set">
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
						{l s='Pop-ups must be enabled in your browser, in order to view the printed labels' mod='bpostshm'}.
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
			<!-- Auto Retour -->
			<div class="clear"></div>
			<div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Retour label' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="auto_retour_label" id="auto_retour_label_1" value="1"{if !empty($auto_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="auto_retour_label_1">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/tick.png" alt="{l s='Yes' mod='bpostshm'}" />{else}{l s='Yes' mod='bpostshm'}{/if}
						</label>
						<input type="radio" name="auto_retour_label" id="auto_retour_label_0" value="0"{if empty($auto_retour_label)} checked="checked"{/if} />
						<label class="col-lg-3" for="auto_retour_label_0">
							{if $version < 1.6}<img src="{$module_dir|escape}views/img/icons/cross.png" alt="{l s='No' mod='bpostshm'}" />{else}{l s='No' mod='bpostshm'}{/if}
						</label>
						<a class="slide-button btn"></a>
					</span>
				</div>
				<div class="margin-form col-lg-9 col-lg-offset-3">
					<p class="preference_description help-block">
						{l s='If you enable this option, a retour label is automatically added and printed when generating labels. If disabled, you are able to manually create retour labels.' mod='bpostshm'}
						<a href="http://bpost.freshdesk.com/support/solutions/articles/4000033756" title="{l s='Click here' mod='bpostshm'}" target="_blank">
						<!-- <a class="info-link" href="#desc-retour-label" title="{l s='Click here' mod='bpostshm'}"> -->
						{l s='Click here' mod='bpostshm'}</a>
						{l s='to learn more about this option.' mod='bpostshm'}
					</p>
					<p class="preference_description help-block" id="desc-retour-label" style="display:none;">
						{l s='IMPORTANT: description retour-label' mod='bpostshm'}
					</p>
				</div>
			</div>
			<!-- T & T integration -->
			<div class="clear"></div>
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
			<!-- Auto update T&T -->
			<!-- <div class="clear"></div>
			<div class="form-group{if empty($label_use_ps_labels)} hidden{/if}">
				<span class="control-label{if $version < 1.6}-bw{/if} col-lg-3">{l s='Other settings' mod='bpostshm'}</span>
				<div class="margin-form col-lg-9">



					{l s='Update T&T status of treated orders every' mod='bpostshm'}
					<select name="label_tt_frequency" class="fixed-width-xs">
						{for $i=1 to 4 nocache}
							<option value="{$i|escape}"{if !empty($label_tt_frequency) && $i == $label_tt_frequency} selected="selected"{/if}>{$i|escape}&nbsp;</option>
						{/for}
					</select> {l s='hour(s)' mod='bpostshm'}
					<div class="clear"></div>



					<p class="checkbox">
						<label for="label_tt_update_on_open">
							<input type="checkbox" name="label_tt_update_on_open" id="label_tt_update_on_open" style="margin-right:2px;" 
							value="1"{if !empty($label_tt_update_on_open)} checked="checked"{/if} />
							{l s='Update T&T status of treated orders automatically when opening orders.' mod='bpostshm'}
						</label>
					</p>
				</div>
			</div> -->
			<br />
			<div class="margin-form panel-footer">
				<button class="button btn btn-default pull-right" type="submit" name="submitLabelSettings">
					<i class="process-icon-save"></i>
					{l s='Save settings' mod='bpostshm'}
				</button>
			</div>
		</fieldset>
	</form>
	<!-- International settings -->
	{if empty($errors)}
		<form class="form-horizontal{if $version < 1.5} v1-4{elseif $version < 1.6} v1-5{/if}" action="#" method="POST" autocomplete="off">
			<fieldset class="panel" id="fs-intl-set">
				<div class="form-group">
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
					<button class="button btn btn-default pull-right" type="submit" name="submitInternationalSettings">
						<i class="process-icon-save"></i>
						{l s='Save settings' mod='bpostshm'}
					</button>
				</div>
				<div class="clear"></div>
				<!-- <div class="form-group">
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
							
							COMMENT THIS NEXT LINE IF SECTION IS VISIBLE
							<a class="info-link" href="#desc-zone-config" title="{l s='Click here' mod='bpostshm'}">

							{l s='Click here' mod='bpostshm'}</a> {l s='to see how this list is created' mod='bpostshm'}
						</p>
						<p class="preference_description help-block" id="desc-zone-config" style="display:none;">
							{l s='IMPORTANT: description zone-config' mod='bpostshm'}
						</p>
					</div>
				</div>
				<div class="clear"></div> -->
				<div style="padding:10px 36px 15px 200px;">
					<p class="preference_description help-block" style="width:100%">
						{l s='To be able to use bpost as a carrier to deliver your parcels, you need to match the PrestaShop countries you want to ship to (including Belgium) with the countries you are allowed to ship to according to your bpost contract.' mod='bpostshm'}
					<br />
						{l s='Below is the list of countries, currently available in your Shipping Manager set-up:' mod='bpostshm'}
					</p>
				</div>
				<div class="form-group">
				<!-- <table class="select-multiple{if empty($country_international_orders) || 1 == $country_international_orders} hidden{/if}"> -->
				<table {if $version >= 1.5}class="select-multiple" {else}style="margin-left:200px;"{/if}>
					<tbody>
						<tr>
							<th width="45%"><i>{l s='Countries activated in the Shipping Manager' mod='bpostshm'}</i></th>
							<!-- <th></th> -->
							<th width="55%">&nbsp;<!-- <i>{l s='Zones to create' mod='bpostshm'}</i> --></th>
						</tr>
						<tr>
							<td>
								<select multiple="multiple" id="country-list">
								{foreach $product_countries as $iso_code => $_country}
									<option value="{$iso_code|escape}">{$_country|escape}</option>
								{/foreach}
								</select>
							</td>
							<!-- <td width="50" align="center">
								<img id="add_country" src="{$module_dir|escape}views/img/icons/arrow-right.png" alt="{l s='Add' mod='bpostshm'}" />
								<br />
								<img id="remove_country" src="{$module_dir|escape}views/img/icons/arrow-left.png" alt="{l s='Remove' mod='bpostshm'}" />
							</td> -->
							<td>
								<!-- <select name="enabled_country_list[]" multiple="multiple" id="enabled-country-list">
								{foreach $enabled_countries as $iso_code => $_country}
									<option value="{$iso_code|escape}">{$_country|escape}</option>
								{/foreach}
								</select> -->
								<!-- <div class="margin-form col-lg-9 col-lg-offset-3"{if $version < 1.5} style="padding:0;font-size:11px;"{/if}> -->
								<div style="color: #7f7f7f;font-size: 0.85em;padding: 0 0 1em 15px;">	
									<p class="preference_description help-block">
										{l s='Please be careful NOT to activate countries in PrestaShop that are not available in your Shipping Manager.' mod='bpostshm'}
									</p>
									<p class="preference_description help-block">
										{l s='Please read more on how to configure PrestaShop zones and countries' mod='bpostshm'}
										<a href="http://bpost.freshdesk.com/support/solutions/articles/4000044096" title="{l s='here' mod='bpostshm'}" target="_blank">
										{l s=' here' mod='bpostshm'}</a>.
									</p>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<img id="get_countries" src="{$module_dir|escape}views/img/ajax-refresh.gif" alt="{l s='Refresh' mod='bpostshm'}" />
								&nbsp;&nbsp;<!-- {l s='Refresh left list' mod='bpostshm'} -->{l s='Refresh list' mod='bpostshm'}
								<br><span id="tracie"></span>
							</td>
						</tr>
					</tbody>
				</table>
				</div>
				<br />
				<!-- <div class="margin-form panel-footer">
					<button class="button btn btn-default pull-right" type="submit" name="submitCountrySettings">
						<i class="process-icon-save"></i>
						{l s='Save settings' mod='bpostshm'}
					</button>
				</div> -->
			</fieldset>
		</form>
		<br />
	{/if}

	<script type="text/javascript">
	$(function() {
		$def_cutoff = '{$cutoff_time|escape}';
		$('input#ship-delay').integerInput({ min: 0, max: 8 });
		$('input#cutoff-time').hourInput({ def: $def_cutoff });
		$('input.del-opt').deliveryOption({ tf: "{l s='as from' mod='bpostshm'}",	tc: "{l s='with additional cost of' mod='bpostshm'}", disables: { "1-350": ["1-300"] }	});
		$('button[name="submitDeliveryOptions"]').on('click', function() {
{literal}			
			var js_delopts = {'1':{},'2':{},'4':{},'9':{}};
{/literal}
			$('input.del-opt').each(function() {
				if (this.checked) {
					var elm = $(this);
						opt_id = elm.data('id'),
						from = elm.attr('from');
					$dm = opt_id.substr(0, 1);
					$opt = opt_id.substr(2);
					js_delopts[$dm][$opt] = (!!elm.attr('cost')) ? [from, elm.attr('cost')] : from;
				}
			});

			$jsn_list = JSON.stringify(js_delopts);
			$('input[name="delivery_options_list"]').val($jsn_list);
		});
		$('button[name="submitDeliverySettings"]').on('click', function() {
			elm_hour = $('input#cutoff-time');
			elm_hour.val(elm_hour.val().replace(/:/g, ''));
		});

		var tip_from = {
			style: { classes:'qtip-bootstrap' },
			content: { text: '' }
		},
		tip_cost = $.extend(true, {}, tip_from);
		tip_from.content.text = "{l s='Minimum purchase total required in order to trigger the option excluding taxes & shipping costs' mod='bpostshm'}";
		tip_cost.content.text = "{l s='added shipping costs' mod='bpostshm'}";
		$('[data-tip="from"]').qtip(tip_from);
		$('[data-tip="cost"]').qtip(tip_cost);
		//
		// $('.delopt-tabs').idTabs();
		// bpost-tabs
		$last_tab = {$last_set_tab|intval};
		$('.bpost-tabs').idTabs({ start: $last_tab });
		$('button[type="submit"]').on('click', function() {
			var idx = $('ul.bpost-tabs li a.selected').parent().index();
			var	elm_hidden = $('<input type="hidden">')
					.attr('name', 'last_set_tab')
					.val(idx);

			$(this).before(elm_hidden);
		});
		// end new items
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
		});

		//
		$('input[name="label_use_ps_labels"]').live('change', function() {
			$(this).closest('.form-group').nextAll('.form-group').toggleClass('hidden');
		});

	});
	</script>
</div>