{*
* 2014-2015 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2015 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if isset($sp)}
	{$ver16 = (1.6 == $version)}
	{if $ver16}
	<div class="adresses_bloc">
		<div class="row">
			<div class="col-xs-12 col-sm-6">
	{else}
	<div class="adresses_bloc clearfix">
	{/if}	
		<br>
		<ul {if $ver16}class="address item box"{else}class="address item"{/if}>
			<li {if $ver16}class="page-subheading"{else}class="address_title"{/if}>{$sp.slug|escape}</li>			 
			<li class="address_company">{$sp.lname|escape}:&nbsp;<span class="address_lastname">{$sp.id|escape}</span></li>
			<li><span class="address_firstname">{$sp.office|escape}</span></li>
			<li><span class="address_address1">{$sp.street|escape} {$sp.nr|escape}</span></li>
			<li><span class="address_postcode">{$sp.zip|escape}</span> <span class="address_city">{$sp.city|escape}</span></li>	
		</ul>
	{if $ver16}
		</div></div></div>
	{/if}
	</div>
{/if}