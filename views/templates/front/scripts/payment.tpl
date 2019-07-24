{literal}<script type="text/javascript" language="JavaScript">
/**
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
 *
 *  @author    Dotpay Team <tech@dotpay.pl>
 *  @copyright Dotpay
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */

$(document).ready(function(){
    if(window.dotpayConfig != undefined) {
        
        (function($) {
            $.fn.goTo = function() {
                $('html, body').animate({
                    scrollTop: ($(this).parents('.dotpay-channels-list').prev().offset().top-100) + 'px'
                }, 'fast');
                return this;
            }
        })(jQuery);
        
        (function($) {
            var defaults = {
                channelsContainerClass: "dotpay-channels-selection",
                channelChangeClass: "channel-selected-change",
                selectedChannelContainerClass: "selectedChannelContainer",
                messageContainerClass: "selected-channel-message",
                collapsibleWidgetTitleClass: "collapsibleWidgetTitle",
                widgetContainerClass: "my-form-widget-container"
            };

            var settings = {};

            $.dpCollapsibleWidget = function(options) {
                if(window.dotpayRegisterWidgetEvent == undefined) {
                    window.dotpayRegisterWidgetEvent = true;
                    settings = $.extend( {}, defaults, options );
                    connectEventToWidget();
                    $('.'+settings.selectedChannelContainerClass+', .'+settings.messageContainerClass).click(function(e){
                        e.stopPropagation();
                        e.preventDefault();
                        return false;
                    });
                    $('.'+settings.channelChangeClass).click(onChangeSelectedChannel);
                }
                return this;
            }
            function connectEventToWidget() {
                $('.channel-container').on('click', function(e) {
                    $('.channel-input', this).prop('checked', true);
                    var id = $(this).find('.channel-input').val();
                    if(id == undefined)
                        return false;
                    var container = copyChannelContainer(id);
                    $('.'+settings.selectedChannelContainerClass+' div').remove();
                    container.insertBefore($('.'+settings.selectedChannelContainerClass+' hr'));
                    toggleWidgetView();
                    e.preventDefault();
                });
            }

            function copyChannelContainer(id) {
                var container = $('.'+settings.widgetContainerClass+' #'+id).parents('.channel-container').clone();
                container.find('.tooltip').remove();
                container.find('.input-container').remove();
                container.removeClass('not-online');
                return container;
            }

            function onChangeSelectedChannel(e) {
                toggleWidgetView();
                e.stopPropagation();
                e.preventDefault();
                return false;
            }

            function toggleWidgetView() {
                $('.'+settings.collapsibleWidgetTitleClass+', .'+settings.selectedChannelContainerClass+' hr, .'+settings.widgetContainerClass).animate(
                    {
                        height: "toggle",
                        opacity: "toggle"
                    }, {
                        duration: "slow"
                    }
                );
                $('.'+settings.messageContainerClass+',.'+settings.selectedChannelContainerClass).show();
            }
        })(jQuery);
        
        $('.dotpay_unsigned_channel a').not('[data-type=dotpay_payment_link]').click(function (e) {
            var target = $(this).find('label[form-target]').attr('form-target');
            var visible = $('form[form-target="' + target + '"]').parents('.dotpay-channels-list').data('visible');
            
            $('form[form-target]').parents('.dotpay-channels-list').hide();
            $('form[form-target]').parents('.dotpay-channels-list').data('visible', false);
            $('form[form-target] button[type="submit"]').attr('disabled', true);
            
            if(visible != true) {
                if('oneclick' === target) {
                    strategyOneClick(target);
                } else if('mp' === target) {
                    strategyMasterPass(target);
                } else if('paypo' === target) {
                    strategyPayPo(target);    
                } else if('cc' === target) {
                    strategyCreditCard(target);
                } else if('pv' === target) {
                    strategyPV(target);
                } else if('blik' === target) {
                    strategyBlik(target);
                } else if('dotpay' === target) {
                    if(window.dotpayConfig.isWidget) {
                        strategyWidget(target);
                    } else {
                        strategyNotWidget(target);
                    }
                }
                $('form[form-target="' + target + '"]').show();
            }
            e.preventDefault();
            return false;
        });
        
        if($('input[name="strategy"]').length == 1)
            $('input[name="strategy"]').click();
        
        $('body').on('input', 'input[name="blik_code"]', function () {
            var target = $(this).parents('form').attr('form-target');
            checkBlikCode(target);
        });

        if(window.dotpayConfig.isWidget == true) {
            $('body').on('click', '.channel-container', function () {
                $(this).parents('form').find('button[type="submit"]').attr('disabled', false);
            });
            $('.my-form-widget-container').parents('label').show();
        }
        $('.oneclick-margin[name=dotpay_type]').change(performActionOC);
        jQuery('#saved_credit_cards').change(changeOcLogo);
        
        /* fix for onepagecheckout module */
        $('a[data-type=dotpay_payment_link]').click(function(e){
             $('div#dp_opacity').show('fade');
             $(this).closest('div.row').find('form.dotpay-form').submit();
        });
        
        /* Fix for jQuery Uniform */
        $('.oneclick-margin').parents('label').addClass('oneclick-margin-label');
    }
});


 $('form.dotpay-form').find('button[type="submit"]').on('click',function(){
	var checkedbylaw = $('input[name="bylaw"]').is(':checked');
	if (checkedbylaw) {
          $('div#dp_opacity').show('fade');
	}
 
 });



function performActionOC() {
    setVisibilityOcLogo();
    if(!jQuery('input#select_saved_card:checked').length)
        jQuery('#saved_credit_cards').attr('disabled', 'disabled');
    else
        jQuery('#saved_credit_cards').removeAttr('disabled');
}

function changeOcLogo() {
    if(window.positionOcLogo === 0)
        window.positionOcLogo = 360;
    else
        window.positionOcLogo = 0;
    if($('input#select_saved_card:checked').length) {
        var logo = $('.dotpay-card-logo');
        if(typeof(logo.transition) === 'function') {
            logo.transition({ rotateY: window.positionOcLogo });
        }
        logo.attr('src', logo.data('card-'+$('#saved_credit_cards').val()));
    }
}

function setVisibilityOcLogo() {
    if(jQuery('input#select_saved_card:checked').length) {
        jQuery('.dotpay-card-logo').show();
    } else {
        jQuery('.dotpay-card-logo').hide();
    }
    changeOcLogo();
}

function checkBlikCode(target) {
    var value = $('input[name="blik_code"]').val();
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', !(value.length == 6 && !isNaN(parseInt(value)) && parseInt(value) == value));
}

function standardStrategy(target) {
    $('form[form-target="' + target + '"]').parents('.dotpay-channels-list').show();
    $('form[form-target="' + target + '"]').parents('.dotpay-channels-list').data('visible', true);
}

function strategyOneClick(target) {
    if($('#saved_credit_cards option').length === 0) {
        $('form[form-target="' + target + '"] input[name=dotpay_type]:last').click().prop('checked', true).parent().addClass('checked');
        $('#saved_credit_cards').attr('disabled', true).parents('label').hide().prev().hide().find('input').attr('disabled', true);
    } else {
        $('form[form-target="' + target + '"] input[name=dotpay_type]:first').click().prop('checked', true).parent().addClass('checked');
        $('#saved_credit_cards').attr('disabled', false).parents('label').show().prev().show().find('input').attr('disabled', false);
    }
    setVisibilityOcLogo();
    standardStrategy(target);
    if(!$('input[name=oneclick_fault]').length)
        $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
}

function strategyMasterPass(target) {
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
    standardStrategy(target);
}

function strategyPayPo(target) {
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
    standardStrategy(target);
}

function strategyCreditCard(target) {
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
    standardStrategy(target);
}

function strategyPV(target) {
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
    standardStrategy(target);
}

function strategyBlik(target) {
    checkBlikCode(target);
    standardStrategy(target);
}

function strategyWidget(target) {
    $.dpCollapsibleWidget();
    $('form[form-target="' + target + '"]').parents('.dotpay-channels-list').show();
    $('form[form-target="' + target + '"]').parents('.dotpay-channels-list').data('visible', true);
    if($('form[form-target="' + target + '"]').find('.channel-container input:checked').length === 1)
        $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false);
    /* Fix for jQuery Uniform */
    if($.uniform !== undefined)
        $.uniform.restore($('.my-form-widget-container input[type="radio"]'));
}

function strategyNotWidget(target) {
    standardStrategy(target);
    $('form[form-target="' + target + '"] button[type="submit"]').attr('disabled', false).goTo();
}
</script>{/literal}
