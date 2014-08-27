<fieldset>
	<legend>{l s='Reference' mod='bpostshm'} {$reference|escape}</legend>
	<ul id="order_actions">{strip}
		{foreach $actions as $action}
			{if empty($action.disabled)}
				<li>
					<a {if !empty($action.target)}href="{$action.href|urldecode}" target="{$action.target|escape}"{else}data-href="{$action.href|urldecode}"{/if} title="{$action.action|escape}">
						{$action.action|escape}
					</a>
				</li>
			{/if}
		{/foreach}
	{/strip}</ul>
</fieldset>

<script type="text/javascript">
	(function($) {
		$(function() {

			$('#order_actions a')
				.on('click', function(e) {
					var $link = $(this),
						href;

					if ('undefined' === typeof $link.data('href'))
						return;

					href = $link.data('href');

					if ('undefined' !== typeof $(this).children(':selected').data('target'))
						return window.open(href);

					$.get(href, { }, function(response) {
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
							return;
						}

					}, 'JSON');
				})
				.children(':disabled').on('click', function() {
					var $option = $(this);
					if ($option.data('disabled'))
						alert($option.data('disabled'));
				});
		});
	})(jQuery);
</script>