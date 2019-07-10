<?php
/**
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
*/

require_once(DOTPAY_PLUGIN_DIR.'/controllers/front/dotpay.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/SellerApi.php');

/**
 * Controller for handling preparing form for Dotpay
 */
class dotpaypreparingModuleFrontController extends DotpayController
{
    /**
     * Preparing hidden form with payment data before sending it to Dotpay
     */
    public function initContent()
    {
        parent::initContent();
        $this->display_column_left = false;
        $this->display_header = false;
        $this->display_footer = false;
        $orderId = 0;
        $getinfoaboutTest = $this->api->getinfoaboutTest();
        if (Tools::getValue('order_id') == false) {
            $this->checkOrderOwnership($this->context->customer->id, $this->context->cart->id_customer);
            $cartId = $this->context->cart->id;											   
            $exAmount = $this->api->getExtrachargeAmount(true);
            if ($exAmount > 0 && !$this->isExVPinCart()) {
                $productId = $this->config->getDotpayExchVPid();
                if ($productId != 0) {
                    $this->module->checkDotpayVirtualProduct();
                    $product = new Product($productId, true);
                    $product->price = $exAmount;
                    $product->save();
                    $product->flushPriceCache();

                    $this->context->cart->updateQty(1, $product->id);
                    $this->context->cart->update();
                    $this->context->cart->getPackageList(true);
                }
            }
            
            $discAmount = $this->api->getDiscountAmount();
            if ($discAmount > 0) {
                $discount = new CartRule($this->config->getDotpayDiscountId());
                $discount->reduction_amount = $this->api->getDiscountAmount();
                $discount->reduction_currency = $this->context->cart->id_currency;
                $discount->reduction_tax = 1;
                $discount->update();
                $this->context->cart->addCartRule($discount->id);
                $this->context->cart->update();
                $this->context->cart->getPackageList(true);
            }

            $secureKey = ($this->getCustomer()->secure_key!==null)?$this->getCustomer()->secure_key:md5(uniqid(rand(), true));
            $this->module->validateOrder(
                $this->context->cart->id,
                (int)$this->config->getDotpayNewStatusId(),
                $this->getDotAmount(),
                $this->module->displayName,
                null,
                array(),
                null,
                false,
                $secureKey
            );
            $orderId = Order::getOrderByCartId($cartId);
            $this->setOrderAsSource($orderId, true);
        } else {								  
            $orderId = Tools::getValue('order_id');
            $this->context->cart = Cart::getCartByOrderId($orderId);
            $this->setOrderAsSource($orderId);
        }
        
        $this->api->onPrepareAction(Tools::getValue('dotpay_type'), array(
            'order' => $orderId,
            'customer' => $this->context->customer->id
        ));
        
        $sa = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
        if ($this->config->isDotpayDispInstruction() &&
            $this->config->isApiConfigOk() &&
            $this->api->isChannelInGroup(Tools::getValue('channel'), array(DotpayApi::CASH_GROUP, DotpayApi::TRANSFERS_GROUP)) &&
            $sa->isAccountRight($this->config->getDotpayApiUsername(), $this->config->getDotpayApiPassword(), $this->config->getDotpayApiVersion())
        ) {
            $this->context->cookie->dotpay_channel = Tools::getValue('channel');
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'confirm', array('order_id' => $orderId)));
            die();
        }
        
        $this->context->smarty->assign(array(
            'hiddenForm' => $this->api->getHiddenForm()
        ));
        $cookie = new Cookie('lastOrder');
        $cookie->orderId = $orderId;
        $cookie->write();
        $this->setTemplate("preparing.tpl");
    }
}
