{*
* 2014-2015 Stigmi
*
* @author Serge <serge@stigmi.eu>
* @copyright 2014-2015 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if isset($sp)}
<div style="background: url({$module_dir|escape}/views/img/sp.png) no-repeat;background-size:auto 70px;padding:20px 0px;">
	<h4 style="margin-left:60px">{$sp.slug|escape}</h4>
	<p>
		{$sp.lname|escape}:&nbsp;{$sp.id|escape}<br>
		{$sp.office|escape}<br>
		{$sp.street|escape} {$sp.nr|escape}<br>
		{$sp.zip|escape} {$sp.city|escape}
	</p>
</div>
{/if}
<br>

