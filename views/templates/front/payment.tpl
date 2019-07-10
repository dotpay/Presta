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
*  @author    Dotpay Team < tech@dotpay.pl >
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, null, "step=3")}" title="{l s='Go back to the Checkout' mod='dotpay'}">{l s='Checkout' mod='dotpay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Dotpay online payment' mod='dotpay'}
{/capture}

<h2 class="page-heading">{l s='Dotpay online payment' mod='dotpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{include file='./chlist.tpl'}

<p class="cart_navigation" style="display: block !important">
    <a href="{$link->getPageLink('order.php', true)}?step=3" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='dotpay'}</a>
</p>
