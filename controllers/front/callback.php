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
require_once(DOTPAY_PLUGIN_DIR.'/classes/Checksum.php');

/**
 * Controller for handling callback from Dotpay
 */
class dotpaycallbackModuleFrontController extends DotpayController
{
   
    
    
    /**
     * Returns if the given ip is on the given whitelist.
    *
    * @param string $ip        The ip to check.
    * @param array  $whitelist The ip whitelist. An array of strings.
    *
    * @return bool
    */
    public function isAllowedIp($ip, array $whitelist)
    {
        $ip = (string)$ip;
        if (in_array($ip, $whitelist, true)) {
            return true;
        }

        return false;
    }
    
    
    /**
     * Confirm payment based on Dotpay URLC
     */
    public function displayAjax()
    {
        /**
        * Check external IP address method
        */

        if( (int)$this->config->getDotpayNonProxy() == 1) {
            $CHECK_IP = $_SERVER['REMOTE_ADDR'];
        }else{
            $CHECK_IP = $this->getClientIp();
        }



        if (!( $this->isAllowedIp($CHECK_IP,$this->config->getDotpayIp()) ))
         {
            die("PrestaShop - UNEXPECTED IP: <br> - REMOTE ADDRESS: ".$this->getClientIp('checkip').'/'.$_SERVER['REMOTE_ADDR']);
         }

        if (getenv('REQUEST_METHOD') != 'POST') {
            die("PrestaShop - ERROR (METHOD <> POST)");
        }
        
        if (!$this->api->checkConfirm()) {
             die("PrestaShop - ERROR SIGNATURE - CHECK PIN");
        }
        
        $api = $this->api;
        if ($api->getOperationType() == $api::PAYMENT_OPERATION) {
            $this->makePayment();
        } elseif ($api->getOperationType() == $api::REFUND_OPERATION) {
            $this->makeRefund();
        } else {
             die('PrestaShop - ERROR OPERATION TYPE');
        }
    }
    
    /**
     * Function which is used to making payments
     */
    private function makePayment()
    {
        $id = ($this->api->isSelectedPvChannel())?$this->config->getDotpayPvId():$this->config->getDotpayId();
        if (Tools::getValue('id') != $id) {
            die("PrestaShop - ERROR ID");
        }
        
        $order = new Order((int)$this->getDotControl(Tools::getValue('control')));
        $brotherOrders = [$order];
        foreach($order->getBrother() as $brotherOrder) {
            $brotherOrders[] = $brotherOrder;
        }
        $currency = new Currency($order->id_currency);
        
        $receivedCurrency = $this->api->getOperationCurrency();
        $orderCurrency = $currency->iso_code;
        
        if ($receivedCurrency != $orderCurrency) {
            die('PrestaShop - NO MATCH OR WRONG CURRENCY - '.$receivedCurrency.' <> '.$orderCurrency);
        }
        
        $receivedAmount = (float)$this->api->getTotalAmount();
        $orderAmount = 0.0;
        foreach ($brotherOrders as $oneOrder) {
            $orderAmount += (float)$this->getCorrectAmount(
                preg_replace("/[^-0-9\.]/", '', str_replace(',', '.',
                    Tools::displayPrice($oneOrder->total_paid, $currency, false)
                ))
            );
        }
        if (number_format($receivedAmount, 4) != number_format($orderAmount, 4)) {
            die('PrestaShop - NO MATCH OR WRONG AMOUNT - '.$receivedAmount.' <> '.$orderAmount);
        }
        
        $cc = DotpayCreditCard::getCreditCardByOrder($order->id);
        if ($cc !== null && $cc->id !== null && $cc->card_id == null) {
            $sellerApi = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());
            $ccInfo = $sellerApi->getCreditCardInfo(
                $this->config->getDotpayApiUsername(),
                $this->config->getDotpayApiPassword(),
                $this->api->getOperationNumber()
            );
            $cc->brand = $ccInfo->brand->name;
            $cc->mask = $ccInfo->masked_number;
            $cc->card_id = $ccInfo->id;
            $cc->save();
            $brand = new DotpayCardBrand($ccInfo->brand->name);
            $brand->name = $ccInfo->brand->name;
            $brand->image = $ccInfo->brand->logo;
            $brand->save();
        }
        
        foreach ($brotherOrders as $order) {
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $lastOrderState = new OrderState($order->getCurrentState());
            
            $newOrderState = $this->api->getNewOrderState($lastOrderState);
            if ($newOrderState===null) {
                die('PrestaShop - WRONG TRANSACTION STATUS');
            }
            
            if (($newOrderState == $this->config->getDotpayNewStatusId() || $newOrderState == _PS_OS_OUTOFSTOCK_UNPAID_) && $lastOrderState->id == $newOrderState) {
                die('OK');
            } else if ($lastOrderState->id != $this->config->getDotpayNewStatusId() &&
                $lastOrderState->id != _PS_OS_ERROR_ &&
                $lastOrderState->id != _PS_OS_OUTOFSTOCK_UNPAID_ &&
                $lastOrderState->id != $this->config->getDotpayWaitingRefundStatusId()) {
                die('PRESTASHOP - STATUS HAS BEEN CHANGED BEFORE');
            }
            if ($lastOrderState->id != $newOrderState) {
                $history->changeIdOrderState($newOrderState, $history->id_order);
                $history->addWithemail(true);
                if ($newOrderState == _PS_OS_PAYMENT_ || $newOrderState == _PS_OS_OUTOFSTOCK_PAID_) {
                    $payments = OrderPayment::getByOrderId($order->id);
                    $numberOfPayments = count($payments);
                    if ($numberOfPayments >= 1) {
                        if (empty($payments[$numberOfPayments - 1]->transaction_id)) {
                            $payments[$numberOfPayments - 1]->transaction_id = $this->api->getOperationNumber();
                            $payments[$numberOfPayments - 1]->payment_method = $this->module->displayName;
                            $payments[$numberOfPayments - 1]->update();
                        } else {
                            $payment = $this->prepareOrderPayment($order);
                            $payment->add();
                        }
                    }
                    $instruction = DotpayInstruction::getByOrderId($order->id);
                    if ($instruction !== null) {
                        $instruction->delete();
                    }
                }
            } else {
                die('PrestaShop - THIS STATE ('.$lastOrderState->name.') IS ALERADY REGISTERED');
            }
        }
        die('OK');
    }
    
    /**
     * Function which is used to making refunds
     */
    private function makeRefund()
    {
        $api = $this->api;
        $statusName = $this->api->getOperationStatusName();
        if ($statusName != $api::OPERATION_COMPLETED && $statusName != $api::OPERATION_REJECTED) {
            die('OK');
        }
        
        $order = new Order((int)$this->getDotControl(Tools::getValue('control')));
        
        if ($statusName == $api::OPERATION_REJECTED) {
            $state = $this->config->getDotpayFailedRefundStatusId();
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState($state, $history->id_order);
            $history->addWithemail(true);
        }
        
        $payments = OrderPayment::getByOrderId($order->id);
        $foundPaymet = false;
        $sumOfPayments = 0.0;
        foreach ($payments as $payment) {
            if ($payment->transaction_id == $this->api->getOperationNumber()) {
                die('PrestaShop - PAYMENT '.$this->api->getOperationNumber().' IS ALREADY SAVED');
            } elseif ($payment->transaction_id == $this->api->getRelatedOperationNumber()) {
                $foundPaymet = true;
            }
            if ($payment->payment_method == $this->module->displayName) {
                $sumOfPayments += (float)$payment->amount;
            }
        }
        if (!$foundPaymet) {
            die('PrestaShop - PAYMENT '.$this->api->getRelatedOperationNumber().' IS NOT SAVED');
        }
        $receivedAmount = (float)($this->api->getTotalAmount());
        
        if ($receivedAmount - $sumOfPayments >= 0.01) {
            die('PrestaShop - NO MATCH OR WRONG AMOUNT - '.$receivedAmount.' > '.$sumOfPayments);
        }
        
        $lastOrderState = new OrderState($order->getCurrentState());
        if ($lastOrderState->id != $this->config->getDotpayWaitingRefundStatusId()) {
            die('PrestaShop - REFUND HAVEN\'T BEEN SUBMITTED');
        }
        
        if ($statusName == $api::OPERATION_COMPLETED) {
            $payment = $this->prepareOrderPayment($order, true);
            $payment->add();

            if ($receivedAmount < $sumOfPayments) {
                $state = $this->config->getDotpayPartialRefundStatusId();
            } else {
                $state = $this->config->getDotpayTotalRefundStatusId();
            }
            
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->changeIdOrderState($state, $history->id_order);
            $history->addWithemail(true);
        }
        die('OK');
    }
    
    /**
     * Creates and prepares payment for given order
     * @param Order $order Order object
     * @param bool $minus Flag, if minus sign should be set
     */
    private function prepareOrderPayment($order, $minus = false)
    {
        $payment = new OrderPayment();
        $payment->order_reference = $order->reference;
        $payment->amount = (float)(($minus ? '-':'').Tools::getValue('operation_original_amount'));
        $payment->id_currency = $order->id_currency;
        $payment->conversion_rate = 1;
        $payment->transaction_id = $this->api->getOperationNumber();
        $payment->payment_method = $this->module->displayName;
        $payment->date_add = new \DateTime();
        return $payment;
    }
    
    /**
     * Returns a correct and well-formatted amount, which is based on input parameter
     * @param float $amount Amount of order
     * @return float
     */
    private function getCorrectAmount($amount)
    {
        $count = 0;
        do {
            $amount = preg_replace("/(\d+)\.(\d{3,})/", "$1$2", $amount, -1, $count);
        } while ($count > 0);
        return $amount;
    }
    
 	/**
    * Get the server variable REMOTE_ADDR, or the first ip of HTTP_X_FORWARDED_FOR (when using proxy)
    * @return string $remote_addr ip of client
    */

    public function getClientIp($list_ip = null)
    {
        $ipaddress = '';
        // CloudFlare support
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            // Validate IP address (IPv4/IPv6)
            if (filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
                $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
                return $ipaddress;
            }
        }
        if (array_key_exists('X-Forwarded-For', $_SERVER)) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['X-Forwarded-For'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipaddress = $ips[0];
            } else {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($list_ip) && $list_ip != null) {
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                return  $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
                return $_SERVER["HTTP_CF_CONNECTING_IP"];
            } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                return $_SERVER["REMOTE_ADDR"];
            }
        } else {
            return $ipaddress;
        }
    }
 





 
}
