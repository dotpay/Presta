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
*
*  @author    Tech Dotpay <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*}
{literal}
<style type="text/css">
    #dotpay-logo {
        width: 20%;
    }
</style>
{/literal}
<div class="box" style="overflow: auto;">
    <h2 class="page-subheading"><a href="http://dotpay.pl" target="_blank"><img id="dotpay-logo" src="{$modules_dir|escape:'htmlall':'UTF-8'}dotpay/views/img/p24_logo.png" alt="Dotpay - {l s='Fast and secure Internet payments' mod='dotpay'}"/></a>&nbsp;{l s='Pay easy with Przelewy24 ( Dotpay module)' mod='dotpay'}</h2>
    {if isset($isRenew) && $isRenew == 1}
    <p>{l s='Your payment was not yet confirmed by Dotpay. If you break payment, you can make it again.' mod='dotpay'}</p>
    <p class="cart_navigation">
        <a class="btn btn-info" href="{$paymentUrl}">
            <span id="proceedPaymentLink">
                {l s='Retry payment' mod='dotpay'}
            </span>
        </a>
    </p>
    {else}
        <p>{l s='Paments provide Dotpay' mod='dotpay'}</p>
    {/if}
    {if isset($isInstruction) && $isInstruction == 1}
        <hr />
        <p>{l s='You can see instruction of payment completion.' mod='dotpay'}</p>
        <p class="cart_navigation">
            <a class="btn btn-info" href="{$instructionUrl}">
                <span id="proceedPaymentLink">
                    {l s='View instruction' mod='dotpay'}
                </span>
            </a>
        </p>
    {/if}
</div>
