{*
* 2014 Stigmi
*
* @author Stigmi.eu <www.stigmi.eu>
* @copyright 2014 Stigmi
* @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="lightbox" class="at-247{if $version >= 1.5 && $version < 1.6} v1-5{/if}">
	{if !empty($step)}
		{if 1 == $step|intval}
			<div class="clearfix">
				<h1 class="col-xs-12"{if $version < 1.5} style="line-height: 4.6em;"{/if}>
					<span class="step">1</span>
					{l s='Select or create an account' mod='bpostshm'}
				</h1>
				<form class="col-xs-12" action="" id="rc-form" method="POST" autocomplete="off"{if $version < 1.5} style="margin-top: 46px;"{/if}>
					<input name="bpack247_register" id="bpack247_register_0" type="radio" value="0" checked="checked" />
					<label for="bpack247_register_0">{l s='I am a registered user' mod='bpostshm'}</label>
					<label for="rc">{l s='RC:' mod='bpostshm'}</label>
					<input type="text" name="rc" id="rc" type="text" value="" placeholder="{l s='123-456-789' mod='bpostshm'}" />
					<a id="rc-info" href="#rc-info-content" title="{l s='Where can I find this info?' mod='bpostshm'}">{l s='Where can I find this info?' mod='bpostshm'}</a>
					<img class="loader" src="{$module_dir|escape}views/img/ajax-loader.gif" alt="{l s='Loading...' mod='bpostshm'}" />
					<br />
					<input name="bpack247_register" id="bpack247_register_1" type="radio" value="1" />
					<label for="bpack247_register_1">{l s='I would like to register for the parcel locker delivery service' mod='bpostshm'}</label>
				</form>
			</div>
			<div id="register" class="clearfix">
				<h2>{l s='What is the parcel locker delivery service ?' mod='bpostshm'}</h2>
				<div class="col-xs-6">
					<p>{l s='Are you rarely at home during the day? Do you have enough time to go to the post office, when you want to send or retrieve packages?' mod='bpostshm'}</p>
					<h3>{l s='Discover the parcel locker delivery service!' mod='bpostshm'}</h3>
					<p>{l s='With parcel lockers, send or retrieve your packages, when and where you want, using the package vending machines in Belgium. They are open 24 hours a day, 7 days a week. Find and register to a vending machine near you to send or pick-up packages.' mod='bpostshm'}</p>
					<p>{l s='You don\'t need to wait at home for the postman or go to the post office: the parcel locker delivery service is easy, convenient, and always available when it suits you.' mod='bpostshm'}</p>
					<h3>{l s='Your advantages:' mod='bpostshm'}</h3>
					<ul>{strip}
						<li>{l s='Parcel lockers are available 24 hours/day, 7 days/week' mod='bpostshm'}</li>
						<li>{l s='You register for free' mod='bpostshm'}</li>
						<!-- <li>{l s='You will receive your packages for free' mod='bpostshm'}</li> -->
						<li>{l s='Shipments are done in a jiffy' mod='bpostshm'}</li>
						<li>{l s='You follow your parcel online' mod='bpostshm'}</li>
						<li>{l s='You can always choose another package dispenser' mod='bpostshm'}</li>
					{/strip}</ul>
					<img src="{$module_dir|escape}views/img/bpack247.png" alt="{l s='bpost 24/7' mod='bpostshm'}" />
				</div>
				<form class="col-xs-6" action="{$url_post_bpack247_register|escape}" id="register-247" method="POST" autocomplete="off" novalidate="novalidate">
					<div class="row clearfix">
						<label for="title">{l s='Title' mod='bpostshm'}</label>
						<select name="id_gender" id="title" required="required">
							{foreach $genders as $_gender}
								<option value="{$_gender->id|intval}"{if $gender == $_gender->id} selected="selected"{/if}>{$_gender->name|escape}</option>
							{/foreach}
						</select>
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="firstname">{l s='Firstname' mod='bpostshm'}</label>
						<input type="text" name="firstname" id="firstname" value="{$firstname|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="lastname">{l s='Lastname' mod='bpostshm'}</label>
						<input type="text" name="lastname" id="lastname" value="{$lastname|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="street">{l s='Street' mod='bpostshm'}</label>
						<input type="text" name="street" id="street" value="{$street|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="number">{l s='Number' mod='bpostshm'}</label>
						<input type="text" name="number" id="number" value="{$number|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="postal_code">{l s='Postal code' mod='bpostshm'}</label>
						<input type="text" name="postal_code" id="postal_code" value="{$postal_code|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="town">{l s='Locality' mod='bpostshm'}</label>
						<input type="text" name="town" id="town" value="{$locality|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="date-of-birth">{l s='Birthday' mod='bpostshm'}</label>
						<input type="text" name="date_of_birth" id="date-of-birth" value="{$birthday|escape}" placeholder="{l s='yyyy-mm-dd' mod='bpostshm'}" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="email">{l s='E-mail' mod='bpostshm'}</label>
						<input type="text" name="email" id="email" value="{$email|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="mobile-number">{l s='Mobile phone' mod='bpostshm'}</label>
						<input type="text" name="mobile_number" id="mobile-number" value="{$mobile_phone|escape}" required="required" />
						<sup>*</sup>
					</div>
					<div class="row clearfix">
						<label for="preferred-language">{l s='Preferred language' mod='bpostshm'}</label>
						<select name="preferred_language" id="preferred-language" required="required">
							{foreach $languages as $iso_code => $_language}
								<option value="{$_language['lang']|escape}"{if $language == $iso_code} selected="selected"{/if}>{$_language['name']|escape}</option>
							{/foreach}
						</select>
						<sup>*</sup>
					</div>
					<div class="row last">
						<input type="checkbox" name="cgv" id="cgv" value="1" required="required" />
						<label for="cgv">{l s='I accept the' mod='bpostshm'} <a id="terms" href="" title="{l s='Terms and conditions' mod='bpostshm'}">{{l s='Terms and conditions' mod='bpostshm'}|lower}</a></label>
						<sup>*</sup>
						<br /><br />
						<input type="submit" class="button" value="{l s='Create account' mod='bpostshm'}" />
					</div>

				</form>
			</div>

			<div id="rc-info-content" title="{l s='Where can I find my RC number?' mod='bpostshm'}">
				<img src="{$module_dir|escape}views/img/card_{$lang_iso|escape}.png" alt="{l s='User card' mod='bpostshm'}" />
				<p>{l s='Your parcel locker RC number is an unique nine-digit code that allows bpost to identify you and notify you when a package comes in the machine. The nine-digit RC number is on your user card and begins with the letters RC.' mod='bpostshm'}</p>
			</div>

			<script type="text/javascript">
				$(function() {

					srgDebug.init('srg-trace');

					var show_close_btn = true,
						close_btn = show_close_btn ? '<div style="color:#e46;position:absolute;top:0px;right:0px;margin:8px;padding:8px;font-size:14px;font-weight:900;cursor:pointer">X</div>' : false;

					$('#rc-info').fancybox({
						fitToView: 	false,
						helpers: {
							title:	null
						},
						maxWidth: 	380,
						tpl : {
						 closeBtn: 	close_btn
						}
					});

					$("#terms").fancybox({
					    type:  			'iframe',
					    href: 			"{l s='http://www.bpost.be/site/fr/docs/conditions/Conditions_generales_bpack247_EN.pdf' mod='bpostshm'}",
					    autoDimensions:	false,
					    width: 			'95%',
					    height: 		'95%',
					    autoScale: 		true,
					    tpl : {
						 closeBtn: 	close_btn
						},
					    helpers : {
				            title : {
				                type: 'inside',
				                position : 'top'
				            }
				        }
					}); 

					// Getbpack247Member
					function getBpack247Member($rcn)
					{
						var dev_mode = false;
						var	actual_error = false,
							polling_time = dev_mode ? 100 : 5000;

						$.getJSON( "{$url_get_bpack247_member|escape:'javascript'}", { rcn: $rcn } )
							.done(function(json) {
						  		actual_error = null != json.Error; //['Error'];
						  		if (actual_error)
						  			//srgDebug.traceJson(json);
						  			srgDebug.trace("{l s='RC# cannot be verified.' mod='bpostshm'}");
						  		else
						  			$(location).attr('href', "{$url_get_point_list|escape:'javascript'}");
						  	})
						  	.fail(function( jqXHR, textStatus, error ) {
						    	var err = textStatus + '<br>' + error + '<br>';
						    	err += jqXHR.responseText;
						    	actual_error = true;
						    	setTimeout(function() {
									if (actual_error)
										srgDebug.trace(err);
								}, polling_time);
							});
					}

					$('input[name="rc"]').live('keyup', function() {
						$rcn = this.value;
						if (reRCn.test($rcn))
							getBpack247Member( $rcn.replace(/-/g, '') );

					});

					$(document)
						.ajaxStart(function() {
							$('.loader').css('display', 'inline-block');
						})
						.ajaxComplete(function() {
							$('.loader').hide();
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

						var dob = $('input[name="date_of_birth"]');
						if ('' != dob.val() && !reDate.test(dob.val())) {
							dob.fadeOut('slow').fadeIn('slow');
							dob.val('');
						}

						var $form 	= $(this),
							$errors = [];

						$.each($form.find('[required]'), function(i, field) {
							var $field = $(field);

							$field.removeClass('error');
							if ($field.is('select, [type="text"]') && '' == field.value)
								$errors.push($field);
							else if ($field.is('#mobile-number')) {
								val = field.value.replace(/[\s\(\)]/g, '');
								if (reMobileBE.test(val))
									field.value = val.substring(val.length-9);
								else
									$errors.push($field);
							}
							else if ($field.is('[type="checkbox"]') && !$field.is(':checked'))
								$errors.push($field{if $version > 1.5}.parent(){/if});

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
							.done(function(response) {
								if (null != response['Error'])
									srgDebug.traceJson(response);
									//srgDebug.trace("{l s='Registration failed.' mod='bpostshm'}");
								else
						  			$(location).attr('href', "{$url_get_point_list|escape:'javascript'}");

							}, "json")
							.fail(function( jqXHR, textStatus, error ) {
						    	var err = textStatus + '<br>' + error + '<br>';
						    	err += jqXHR.responseText;
						    	//srgDebug.trace(err);
							});

					});
				});
			</script>
		{/if}
	{/if}
</div>