{*
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*  @author    Dotpay Team <tech@dotpay.pl>
*
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*}

{if $regMessEn}
<div class="panel"><div class="dotpay-offer">
    <h3>{l s='Registration' mod='dotpay'}</h3>
    <p>{l s='In response to the market\'s needs Dotpay has been delivering innovative Internet payment services providing the widest e-commerce solution offer for years. The domain is money transfers between a buyer and a merchant within a complex service based on counselling and additional security. Within an offer of Internet payments Dotpay offers over 140 payment channels including: mobile payments, instalments, cash, e-wallets, transfers and credit card payments.' mod='dotpay'}</p>
    <p>{l s='To all new clients who have filled in a form and wish to accept payments we offer promotional conditions:' mod='dotpay'}</p>
    <ul>
        <li><b>1,9%</b> {l s='commission on Internet payments (not less than PLN 0.30) ' mod='dotpay'}</li>
        <li>{l s='instalment payments' mod='dotpay'} <b>{l s='without any commission!' mod='dotpay'}</b></li>
        <li>{l s='an activation fee - only PLN 10' mod='dotpay'}</li>
        <li><b>{l s='without any additional fees' mod='dotpay'}</b> {l s='for refunds and withdrawals!' mod='dotpay'}</li>
    </ul>
    <p>{l s='In short, minimizing effort and work time you will increase your sales possibilities. Do not hesitate and start your account now!' mod='dotpay'}</p>
    <div class="cta-button-container">
        <a href="http://www.dotpay.pl/prestashop/" class="cta-button">{l s='Register now!' mod='dotpay'}</a>
    </div>
</div></div>
{/if}

<div class="panel">
    <div class="dotpay-config">
        <h3>{l s='Inforation' mod='dotpay'}</h3>
        <a href="http://www.dotpay.pl" target="_blank" title="www.dotpay.pl"><img src="{$moduleMainDir|escape:'htmlall':'UTF-8'}views/img/dotpay_logo85.png" width="85px" height="50px" border="0" /></a>
        {if $confOK}
            <div class="bootstrap">
                <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Module is active. ' mod='dotpay'}</h2>
                    <br />
                    <p style="color: #555;"><b>{l s='If you do not recive payment information, please check URLC configuration in your Dotpay user panel.' mod='dotpay'}</b></p>
                    <p style="color: #D27C82;"><b>{if $testMode}{l s='Module is in TEST mode. All payment information is fake!' mod='dotpay'}{/if}</b></p><br><br>
                    <p style="color: #D27C82;"><b>{if $oldVersion}{l s='This version of PrestaShop does not support currencies other that PLN. Please update your PrestaShop installation to the latest version if you want to use other currencies!' mod='dotpay'}{/if}</b></p>
                </div>
            </div>
        {else}
            <div class="bootstrap">
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Module is not active. Please check your configuration.' mod='dotpay'}</h2>
                    <br />
                    <p style="color: #555;"><b>{l s='ID and PIN can be found in Dotpay panel in Settings in the top bar. ID number is a 6-digit string after # in a "Shop" column.' mod='dotpay'}</b></p>
                    <br />
                </div>
            </div>
        {/if}
        {if $testSellerId === false}
            <div class="bootstrap">
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your seller ID is incorrect.' mod='dotpay'}</h2>
                    <br />
                    <p style="color: #555;"><b>{l s='Please check your ID and Test mode settings.' mod='dotpay'}</b></p>
                    <br />
                </div>
            </div>
        {/if}
        {if $testApiAccount === false}
            <div class="bootstrap">
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your username or password for API is incorrect.' mod='dotpay'}</h2>
                    <br />
                    <p style="color: #555;"><b>{l s='Please check your API configuration.' mod='dotpay'}</b></p>
                    <br />
                </div>
            </div>
        {/if}
        {if $testSellerPin === false && $testApiAccount === true}
            <div class="bootstrap">
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your PIN is incorrect.' mod='dotpay'}</h2>
                    <br />
                    <p style="color: #555;"><b>{l s='Please type correct PIN. Until it payments will not be accepted.' mod='dotpay'}</b></p>
                    <br />
                </div>
            </div>
        {/if}

        <p>{l s='Thanks to Dotpay payment module the only activities needed for integration are: ID and PIN numbers and URLC confirmation configuration.' mod='dotpay'}</p>
        <p>{l s='ID and PIN can be found in Dotpay panel in Settings in the top bar. ID number is a 6-digit string after # in a "Shop" column.' mod='dotpay'}</p>
        <p>{l s='URLC configuration is just setting an address to which information about payment should be directed. This address is:' mod='dotpay'} <b>{$targetForUrlc|escape:'htmlall':'UTF-8'}</b></p>
        <p>{l s='Your shop is going to automatically send URLC address to Dotpay.' mod='dotpay'}</p><br>
        <p><b style="color: brown;">{l s='Only thing You have to do is log in to the Dotpay user panel and untick "Block external URLC" option in Settings -> Notifications -> Urlc configuration -> Edit.' mod='dotpay'}</b></p>
        <p><b style="color: brown;">{l s='If your shop does not use HTTPS protocol you should also disable HTTPS verify and SSL certificate verify.' mod='dotpay'}</b></p>
    </div>
</div>

<div class="panel"><div class="dotpay-config-state">
    <h3>{l s='Updates' mod='dotpay'}</h3>
    <h4>{l s='Version of this module is: ' mod='dotpay'}<strong>{$moduleVersion|escape:'htmlall':'UTF-8'}</strong>.</h4>
    {if $obsoletePlugin}
        <div class="bootstrap">
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your plugin is obsolete!' mod='dotpay'}</h2>
                <br />
                <p style="color: #555;">
                    {l s='You can download the latest version from' mod='dotpay'}
                    <a href="{$urlWithNewVersion|escape:'htmlall':'UTF-8'}" target="_blank">{l s='this page' mod='dotpay'}</a>.
                </p>
            </div>
        </div>
    {elseif $canNotCheckPlugin}
        <div class="bootstrap">
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Can not check the update' mod='dotpay'}</h2>
                <br />
                <p style="color: #555;">
                    {l s='You can manually check the latest version' mod='dotpay'}
                    <a href="https://github.com/dotpay/PrestaShop-1.6/releases/latest" target="_blank">{l s='on this page' mod='dotpay'}</a>.
                </p>
            </div>
        </div>
    {else}
        <div class="bootstrap">
            <div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your module is up to date.' mod='dotpay'}</h2>
                <br />
                <p style="color: #555;">
                    {l s='This gives you the guarantee of security and the ability to use the latest solutions offered by Dotpay.' mod='dotpay'}
                </p>
            </div>
        </div>
    {/if}
    {if $badPhpVersion}
        <div class="bootstrap">
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h2 style="margin-left: 10px; margin-top: 0px;">{l s='Your PHP version is obsolete:' mod='dotpay'}&nbsp;{$phpVersion|escape:'htmlall':'UTF-8'}</h2>
                <br />
                <p style="color: #555;"><b>{l s='This plugin might work incorrectly. Please update your PHP version to at least' mod='dotpay'}&nbsp;{$minorPhpVersion|escape:'htmlall':'UTF-8'}</b></p>
                <br />
            </div>
        </div>
    {/if}
	  <h2>{l s='Check manual before configuration:'  mod='dotpay'}<a href="{l s='https://github.com/dotpay/PrestaShop-1.6/releases/download/v2.2.2/Dotpay_PrestaShop_module-manual_v2.2.2_en.pdf'  mod='dotpay'}" Title="{l s='Get manual for this module' mod='dotpay'}" target="_blank"> {l s='download manual' mod='dotpay'}</a></h2>
</div></div>

{literal}
<script type="text/javascript">
    var badNewID = '{/literal}{$badNewIdMessage|escape:'htmlall':'UTF-8'}{literal}';
    var badOldID = '{/literal}{$badOldIdMessage|escape:'htmlall':'UTF-8'}{literal}';
    var badNewPIN = '{/literal}{$badNewPinMessage|escape:'htmlall':'UTF-8'}{literal}';
    var badOldPIN = '{/literal}{$badOldPinMessage|escape:'htmlall':'UTF-8'}{literal}';
    var valueLowerThanZero = '{/literal}{$valueLowerThanZero|escape:'htmlall':'UTF-8'}{literal}';
    var carrierNotset = '{/literal}{$carriernotselectedMessage|escape:'htmlall':'UTF-8'}{literal}';
    var badID = '';
    var badPin = '';

    function setFieldsForApi() {
        var apiVersion = $('.api-select').val();
        badID = (apiVersion=='dev')?badNewID:badOldID;
        badPin = (apiVersion=='dev')?badNewPIN:badOldPIN;
        if(apiVersion=='legacy') {
            $('.dev-option').parents('.form-group').hide().next('hr').hide();
            $('.legacy-option').parents('.form-group').show().next('hr').show();
            $('#message-for-old-version').show();
        } else {
            $('.legacy-option').parents('.form-group').hide().next('hr').hide();
            $('.dev-option').parents('.form-group').show().next('hr').show();
            $('#message-for-old-version').hide();
            setFieldsForPostponed();
            setFieldsForRenew();
            setFieldsForPV();
            setFieldsForExCh();
            setFieldsForDiscount();

        }
    }



    function setFieldsForPostponed() {
        if($('.postponed-enable-option input[name="DP_POSTPONED_PAYMENT"]:checked').val()=='1') {
            $('div#fieldset_1_1').show();
              var check1 = validateId($('#DP_USER_ID'), true) + validatePin($('#DP_USER_PIN'), true) +  validatePostponened();
               if(check1) {
                    disableSubmit(true);
               } else {
                    disableSubmit(false);
               }


        } else {
            $('div#fieldset_1_1').hide();

               var check2 = validateId($('#DP_USER_ID'), true) + validatePin($('#DP_USER_PIN'), true);
               if(check2) {
                    disableSubmit(true);
               } else {
                    disableSubmit(false);
               }
        }
    }

    function setFieldsForRenew() {
        if($('.renew-enable-option input[name="DP_RENEW"]:checked').val()=='1') {
            $('.renew-option').parents('.form-group').show();
        } else {
            $('.renew-option').parents('.form-group').hide();
        }
    }

    function setFieldsForPV() {
        if($('.pv-enable-option input[name="DP_PV_MODE"]:checked').val()=='1') {
            $('.pv-option').parents('.form-group').show();
        } else {
            $('.pv-option').parents('.form-group').hide();
        }
    }

    function setFieldsForExCh() {
        if($('.excharge-enable-option input[name="DP_EXCH_EN"]:checked').val()=='1') {
            $('.exch-option').parents('.form-group').show();
        } else {
            $('.exch-option').parents('.form-group').hide();
        }
    }

    function setFieldsForDiscount() {
        if($('.discount-enable-option input[name="DP_DISC_EN"]:checked').val()=='1') {
            $('.discount-option').parents('.form-group').show();
        } else {
            $('.discount-option').parents('.form-group').hide();
        }
    }



    function disableSubmit(mode) {
        $("button[name=saveDotpayConfig]").prop("disabled", mode);
    }

    function prepareValidation() {
        $('.form-group').find('.col-lg-9').append('<span class="errorMessage"></span>');
    }

    function setError(obj, message) {
        obj.parents('.form-group').find('.errorMessage').html(message);
    }

    function validateId(idElem, empty) {
        var idLength = idElem.val().length;
        if(empty===true && idLength === 0) {
            return 0;
        }
        if((idLength!=6&&$('#DP_API_VERSION').val()=='dev')||
           ((idLength!=5&&idLength!=4)&&$('#DP_API_VERSION').val()=='legacy') ||
           (isNaN(idElem.val() % 1))
        ) {
            setError(idElem, badID);
            return 1;
        } else {
            setError(idElem, '');
            return 0;
        }
    }

    function validatePin(pinElem, empty) {
        var pinLength = pinElem.val().length;
        if(empty===true && pinLength === 0) {
            return true;
        }
        if(($('#DP_API_VERSION').val()=='dev' && (pinLength>32 || pinLength<16)) ||
           ($('#DP_API_VERSION').val()=='legacy' && (pinLength!=16 && pinLength!=0))){
            setError(pinElem, badPin);
            return 1;
        } else {
            setError(pinElem, '');
            return 0;
        }
    }

    function validateLTZ(obj) {
        if(parseFloat(obj.val())<0) {
            setError(obj, valueLowerThanZero);
            return 1;
        } else {
            setError(obj, '');
            return 0;
        }
    }


function selectValidationPostponed() {
  var selectIsValid = true;
        $('.dp_selectmenupostponed').each(function() {
        if ($(this).val() === '') {
        selectIsValid = false;
        return; // skip remaining checks
        }
    });
    return selectIsValid;
}


    function validatePostponened(){
             if($('.postponed-enable-option input[name="DP_POSTPONED_PAYMENT"]:checked').val()=='1') {
                  if(selectValidationPostponed() === true)   {
                    return 0;
                  }   else {
                    return 1;
                  }
             }
    }


    function validateGUI(check) {
        setFieldsForApi();
        if(check == undefined)
            var check = 0;
        check += validateId($('#DP_USER_ID'));
        check += validatePin($('#DP_USER_PIN'));
        check += validatePostponened();

        if($('#DP_API_VERSION').val()=='dev') {
            if($('.pv-enable-option input[name="DP_PV_MODE"]:checked').val()=='1') {
                check += validateId($('#DP_PV_ID'), check);
                check += validatePin($('#DP_PV_PIN'), check);
            }
            check += validateLTZ($('#DP_EXCH_AM'));
            check += validateLTZ($('#DP_EXCH_PERC'));
            check += validateLTZ($('#DP_DISC_AM'));
            check += validateLTZ($('#DP_DISC_PERC'));
        }
        if(check > 0)
            disableSubmit(true);
        else
            disableSubmit(false);
    }

    function setVisibilityForAdvancedMode() {
        if($('[name=DP_ADVANCED_MODE]:checked').val() == '1')
            $('#advanced-settings').css('display','block');
        else
            $('#advanced-settings').css('display','none');
    }

    function resetselectedDeliverymethodDotpay() {
            var DeliveryCarrierVar = {/literal}{$deliverylistmethod|@json_encode nofilter};{literal}

            if(DeliveryCarrierVar != null) {
                $.each(DeliveryCarrierVar, function (key, data) {
                    var key2 = '#' + key;
                    $(key2).find('option').removeAttr("selected");

              })
            }

    }

    function setselectedDeliverymethodDotpay() {
            var DeliveryCarrierVar = {/literal}{$deliverylistmethod|@json_encode nofilter};{literal}

            if(DeliveryCarrierVar != null) {
                $.each(DeliveryCarrierVar, function (key, data) {

                    var key2 = '#' + key;
                    var children = $(key2).children();

                    for(var i=0;i<children.length; i++){
                        if (children[i].value == data) {
                            children[i].selected = true;
                            break;
                        }
                    }
              })
            }
    }


   function PINvisibleEye() {

        $("i#eyelook").click(function() {

            $('i#eyelook').toggleClass("icon-eye-slash icon-eye");
            var input = $('input#DP_USER_PIN');
            if (input.attr("type") == "password") {
                input.attr("type", "text");
                 $('i#eyelook').attr('style', 'color : #97224b; cursor : zoom-out;');
            } else {
                input.attr("type", "password");
                $('i#eyelook').attr('style', 'color : #2eacce; cursor : zoom-in;');
            }
        });
   }


    $(document).ready(function(){
        $('.password-field').attr('type', 'password');
        $('.lastInSection').parents('.form-group').after('<hr />');

        $('input#DP_USER_PIN').attr("type", "password");

        $('<div id="advanced-settings"></div>').insertAfter($('.advanced-mode-switch').parents('.form-group'));
        $('#advanced-settings').nextAll().detach().appendTo('#advanced-settings');
        $('<hr style="height: 2px; background-color: #2eacce;" />').prependTo('#advanced-settings');
        $('[name=DP_ADVANCED_MODE]').change(setVisibilityForAdvancedMode);
        setVisibilityForAdvancedMode();
        PINvisibleEye();
        prepareValidation();
        setFieldsForApi();
        resetselectedDeliverymethodDotpay();
        setselectedDeliverymethodDotpay();


		$(".dp_selectmenupostponed").change(function() {
            var dp_check3 = validateId($('#DP_USER_ID'), true) + validatePin($('#DP_USER_PIN'), true) +  validatePostponened();
                 if(dp_check3) {
                       disableSubmit(true);
                   } else {
                        disableSubmit(false);
                   }

                if(validatePostponened()) {
                        $("#DotcarrierError").html('<i class="icon-exclamation-circle"></i> ' + carrierNotset);
                    } else {
                        $("#DotcarrierError").html('');
                    }
    	});

        //remove spaces from ID, PIN input
            $("input#DP_USER_PIN").attr("autocomplete", "off");
            $("input#DP_USER_PIN").attr("disabled", "disabled");
			$("input#DP_USER_PIN").bind('keyup paste keydown', function(e) {
				$(this).val(function(_, v){
					return v.replace(/\s+/g, '');
				});
    		});

            $("input#DP_PV_PIN").attr("autocomplete", "off");
            $("input#DP_PV_PIN").attr("disabled", "disabled");
			$("input#DP_PV_PIN").bind('keyup paste keydown', function(e) {
				$(this).val(function(_, v){
					return v.replace(/\s+/g, '');
				});
    		});


            // ID
             $("input#DP_USER_ID").attr("autocomplete", "off");
             $("input#DP_USER_ID").attr("disabled", "disabled");
             $("input#DP_USER_ID").attr("pattern", "[0-9]{4,6}");
             $("input#DP_USER_ID").attr("maxlength", "6");
             $("input#DP_USER_ID").bind('keyup paste keydown', function(e) {
                if (/\D/g.test(this.value)) {
                    // Filter non-digits from input value.
                    this.value = this.value.replace(/\D/g, '');
                }
                });

             $("input#DP_PV_ID").attr("autocomplete", "off");
             $("input#DP_PV_ID").attr("disabled", "disabled");
             $("input#DP_PV_ID").attr("pattern", "[0-9]{4,6}");
             $("input#DP_PV_ID").attr("maxlength", "6");
             $("input#DP_PV_ID").bind('keyup paste keydown', function(e) {
                if (/\D/g.test(this.value)) {
                    // Filter non-digits from input value.
                    this.value = this.value.replace(/\D/g, '');
                }
                });

        //currency
        $("input#DP_PV_CUR").attr("pattern", "^([A-Z]{3}?\,?)+([A-Z]{3})$");

        $('input#DP_PV_CUR').bind('keyup blur', function () {
            $(this).val($(this).val().replace(/[^A-Z,]/g, ''))
        });


        $("input#DP_WIDGET_DIS_CURR").attr("pattern", "^([A-Z]{3}?\,?)+([A-Z]{3})$");

        $('input#DP_WIDGET_DIS_CURR').bind('keyup blur', function () {
            $(this).val($(this).val().replace(/[^A-Z,]/g, ''))
        });


             $("input#DP_RENEW_DAYS").attr("pattern", "[0-9]{0,6}");
             $("input#DP_RENEW_DAYS").attr("maxlength", "6");
             $("input#DP_RENEW_DAYS").bind('keyup paste keydown', function(e) {
                if (/\D/g.test(this.value)) {
                    // Filter non-digits from input value.
                    this.value = this.value.replace(/\D/g, '');
                }
                });

// input: login and password for api
        $("input#DP_DISC_API_UNAME").attr("autocomplete", "off");
        $("input#DP_DISC_API_UNAME").attr("disabled", "disabled");

        $("input#DP_DISC_API_PASSWD").attr("autocomplete", "off");
        $("input#DP_DISC_API_PASSWD").attr("disabled", "disabled");

        $("input#DP_DISC_API_UNAME").bind('keyup paste keydown', function(e) {
            $(this).val(function(_, v){
                return v.replace(/\s+/g, '');
            });
        });

        $("input#DP_DISC_API_PASSWD").bind('keyup paste keydown', function(e) {
            $(this).val(function(_, v){
                return v.replace(/\s+/g, '');
            });
        });

        var check = validateId($('#DP_USER_ID'), true) + validatePin($('#DP_USER_PIN'), true) +  validatePostponened();
        if(check)
            disableSubmit(true);


      $('.postponed-enable-option input[name="DP_POSTPONED_PAYMENT"]').change(function(){
            setFieldsForPostponed();
        });


        $('.renew-enable-option input[name="DP_RENEW"]').change(function(){
            setFieldsForPV();
            validateGUI();
        });

        $('.pv-enable-option input[name="DP_PV_MODE"]').change(function(){
            setFieldsForPV();
            validateGUI();
        });

        $('.excharge-enable-option input[name="DP_EXCH_EN"]').change(function(){
            setFieldsForExCh();
        });

        $('.discount-enable-option input[name="DP_DISC_EN"]').change(function(){
            setFieldsForDiscount();
        });

        $('.api-select,#DP_USER_ID,#DP_USER_PIN,#DP_PV_ID,#DP_PV_PIN,#DP_EXCH_AM,#DP_EXCH_PERC,#DP_DISC_AM,#DP_DISC_PERC').change(function(){
            validateGUI();
            validatePostponened();
        });

        setTimeout(function() {
            $("#DP_USER_ID").removeAttr("disabled");
            $("#DP_PV_ID").removeAttr("disabled");
            $("#DP_PV_PIN").removeAttr("disabled");
            $("#DP_USER_PIN").removeAttr("disabled");
            $("#DP_DISC_API_UNAME").removeAttr("disabled");
            $("#DP_DISC_API_PASSWD").removeAttr("disabled");
        }, 300);


    });
</script>
<style>

    div#fieldset_1_1.panel > div.form-wrapper {
        margin-top: 70px;
    }

    .dotpayadvsett{
        color: #c64aca;
        font-weight: bold;
    }

    #DotcarrierError {
        text-align:center;
        color:red;
        text-transform: none;
        font: 400 14px/1.42857 "Open Sans",Helvetica,Arial,sans-serif;
    }


</style>
{/literal}
