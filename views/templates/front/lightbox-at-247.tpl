{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="lightbox" class="at-247{if $version < 1.6} v1-5{/if}">
	{if !empty($step)}
		{if 1 == $step|intval}
			<div class="row clearfix">
				<h1 class="col-xs-12">
					<span class="step">1</span>
					{l s='Select or create a bpack 24/7 account' mod='bpostshm'}
				</h1>
				<form class="col-xs-12" action="" id="rc-form" method="POST" autocomplete="off">
					<input name="bpack247_register" id="bpack247_register_0" type="radio" value="0" />
					<label for="bpack247_register_0">{l s='I am a bpack 24/7 registered user' mod='bpostshm'}</label>
					<label for="rc">{l s='RC:' mod='bpostshm'}</label>
					<input type="text" name="rc" id="rc" type="text" value="" placeholder="{l s='123-456-789' mod='bpostshm'}" />
					<a id="rc-info" href="#rc-info-content" title="{l s='Where can I find this info?' mod='bpostshm'}">{l s='Where can I find this info?' mod='bpostshm'}</a>
					<br />
					<input name="bpack247_register" id="bpack247_register_1" type="radio" value="1" />
					<label for="bpack247_register_1">{l s='I would like to register for pack 24/7' mod='bpostshm'}</label>
				</form>
			</div>
			<div id="register" class="row clearfix">
				<h2>{l s='What is bpack 24/7 ?' mod='bpostshm'}</h2>
				<div class="col-xs-6">
					<p>{l s='Are you rarely at home during the day? Do you have enough time to go when you want to send or retrieve packages to the post office?' mod='bpostshm'}</p>
					<h3>{l s='Discover bpack 24/7!' mod='bpostshm'}</h3>
					<p>{l s='With bpack 24/7 send or take your packages on when and where you want with the package vending machines in Belgium. They are open 24 hours a day, 7 days a week. Find and register to a vending machine near you to send or pick-up packages.' mod='bpostshm'}</p>
					<p>{l s='You don\'t need to wait at home for the postman or go to the post office: bpack 24/7 is easy, convenient, and always available when it suits you.' mod='bpostshm'}</p>
					<h3>{l s='Your advantages:' mod='bpostshm'}</h3>
					<ul>{strip}
						<li>{l s='bpack 24/7 is available 24 hours/day, 7 days/week' mod='bpostshm'}</li>
						<li>{l s='You register for free' mod='bpostshm'}</li>
						<li>{l s='You will receive your packages for free' mod='bpostshm'}</li>
						<li>{l s='Shipments are done in a jiffy' mod='bpostshm'}</li>
						<li>{l s='You follow your parcel online' mod='bpostshm'}</li>
						<li>{l s='You can always choose another package dispenser' mod='bpostshm'}</li>
					{/strip}</ul>
					<img src="{$module_dir|escape}views/img/bpack247.png" alt="{l s='bpost 24/7' mod='bpostshm'}" />
				</div>
				<form class="col-xs-6" action="{$url_post_bpack247_register|escape:'url'}" id="register-247" method="POST" autocomplete="off" novalidate="novalidate">
					<div class="row clearfix">
						<label for="title">{l s='Title' mod='bpostshm'}</label>
						<select name="id_gender" id="title" required="required">
							{foreach $genders as $_gender}
								<option value="{$_gender->id}"{if $gender == $_gender->id} selected="selected"{/if}>{$_gender->name}.</option>
							{/foreach}
						</select>
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="firstname">{l s='Firstname' mod='bpostshm'}</label>
						<input type="text" name="firstname" id="firstname" value="{$firstname|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="lastname">{l s='Lastname' mod='bpostshm'}</label>
						<input type="text" name="lastname" id="lastname" value="{$lastname|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="street">{l s='Street' mod='bpostshm'}</label>
						<input type="text" name="street" id="street" value="{$street|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="number">{l s='Number' mod='bpostshm'}</label>
						<input type="text" name="number" id="number" value="{$number|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="postal_code">{l s='Postal code' mod='bpostshm'}</label>
						<input type="text" name="postal_code" id="postal_code" value="{$postal_code|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="town">{l s='Locality' mod='bpostshm'}</label>
						<input type="text" name="town" id="town" value="{$locality|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="date-of-birth">{l s='Birthday' mod='bpostshm'}</label>
						<input type="text" name="date_of_birth" id="date-of-birth" value="{$birthday|default:''}" placeholder="{l s='dd/mm/yyyy' mod='bpostshm'}" required="required" />
						<sup>*</sup>
						<span class="infos">{l s='dd/mm/yyyy' mod='bpostshm'}</span>
					</div>
					<div class="row clearfix">
						<label for="email">{l s='E-mail' mod='bpostshm'}</label>
						<input type="text" name="email" id="email" value="{$email|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="mobile-number">{l s='Mobile phone' mod='bpostshm'}</label>
						<input type="text" name="mobile_number" id="mobile-number" value="{$mobile_phone|default:''}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="preferred-language">{l s='Preferred language' mod='bpostshm'}</label>
						<select name="preferred_language" id="preferred-language" required="required">
							{foreach $languages as $iso_code => $_language}
								<option value="{$_language['lang']}"{if $language == $iso_code} selected="selected"{/if}>{$_language['name']}</option>
							{/foreach}
						</select>
						<sup>*</sup>
					</div>
					<div class="row last">
						<input type="checkbox" name="cgv" id="cgv" value="1" required="required" />
						<label for="cgv">{l s='I accept the' mod='bpostshm'} <a href="{l s='https://www.bpack247.be/en/general-terms-conditions.aspx' mod='bpostshm'}"
							title="{l s='Terms and conditions' mod='bpostshm'}" target="_blank">{{l s='Terms and conditions' mod='bpostshm'}|lower}</a></label>
						<sup>*</sup>
						<br /><br />
						<input type="submit" class="button" value="{l s='Create account' mod='bpostshm'}" />
					</div>

				</form>
			</div>

			<div id="rc-info-content" title="{l s='Where can I find my user ID?' mod='bpostshm'}">
				<img src="{$module_dir|escape}views/img/card_{$lang_iso|escape}.png" alt="{l s='User card' mod='bpostshm'}" />
				<p>{l s='Your bpack 24/7 user number is an unique nine-digit code that allows bpost to identify you and notify you when a package comes in the machine. The nine-digit user number is on your user card and begins with the letters RC.' mod='bpostshm'}</p>
			</div>
			<script type="text/javascript">
				$(function() {
					$('#rc-info').fancybox({
						fitToView: 	false,
						helpers:	{
							title:		null
						},
						maxWidth	: 380
					});

					$('input[name="bpack247_register"]').live('change', function() {
						if (this.value > 0)
							$('#register').show();
						else
							$('#register').hide();
						parent.$.fancybox.update();
					});

					$('#register-247').live('submit', function(e) {
						e.preventDefault();
						e.stopPropagation();

						var $form 	= $(this),
							$errors = [];

						$.each($form.find('[required]'), function(i, field) {
							var $field = $(field);

							$field.removeClass('error');
							if ($field.is('select, [type="text"]') && '' == field.value)
								$errors.push($field);
							else if ($field.is('[type="checkbox"]') && !$field.is(':checked'))
								$errors.push($field);
						});

						if ($errors.length)
						{
							$.each($errors, function(i, $field) {
								$field.addClass('error')
									.fadeOut('slow', function(){
										$(this).fadeIn('slow');
									});
							});
							return;
						}

						$.post( $form.attr('action'), $form.serialize() )
							.success(function(response) {
								if (response)
									$(location).attr('href', '{$url_get_point_list|escape:'javascript'}');
								else
									alert('{l s='An error has occured, please try again later.' js=1 mod='bpostshm'}');
							});
					});
				});
			</script>
		{/if}
	{/if}
</div>