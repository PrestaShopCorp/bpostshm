{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}
<option value="{$href}"
		{if !empty($disabled)} disabled="disabled" data-disabled="{$disabled}"{/if}
		{if !empty($target)} data-target="{$target|escape}"{/if}>
	{$action}
</option>