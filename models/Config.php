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

/**
 * Manage the configuration
 */
class DotpayConfig
{
    /**
     *
     * @var string Dotpay target url for legacy api 
     */
    private static $DOTPAY_TARGET_URL_LEGACY = 'https://ssl.dotpay.pl/';
    
    /**
     *
     * @var string Dotpay target url for dev api 
     */
    private static $DOTPAY_TARGET_URL_DEV = 'https://ssl.dotpay.pl/t2/';
    
    /**
     *
     * @var string Dotpay target url for test payment 
     */
    private static $DOTPAY_TARGET_URL_TEST_DEV = 'https://ssl.dotpay.pl/test_payment/';
    
    /**
     *
     * @var string Dotpay target url for test payment 
     */
    private static $DOTPAY_SELLER_API_URL = 'https://ssl.dotpay.pl/s2/login/';
    
    /**
     *
     * @var string Dotpay target url for test payment 
     */
    private static $DOTPAY_TEST_SELLER_API_URL = 'https://ssl.dotpay.pl/test_seller/';
    
    /**
     * Returns Dotpay target url
     * @return string
     */
    public function getDotpayTargetUrl()
    {
        if ($this->getDotpayApiVersion()=='legacy') {
            return self::$DOTPAY_TARGET_URL_LEGACY;
        } elseif ($this->isDotpayTestMode()) {
            return self::$DOTPAY_TARGET_URL_TEST_DEV;
        } else {
            return self::$DOTPAY_TARGET_URL_DEV;
        }
    }
    
    /**
     * Returns Dotpay seller Api url
     * @return type
     */
    public function getDotpaySellerApiUrl()
    {
        $dotSellerApi = self::$DOTPAY_SELLER_API_URL;
        if ($this->isDotpayTestMode()) {
            $dotSellerApi = self::$DOTPAY_TEST_SELLER_API_URL;
        }
        
        return $dotSellerApi;
    }
    
    /**
     * returns Dotpay IP address
     * @return string
     */
    public function getDotpayIp()
    {
        return '195.150.9.37';
    }
    
    /**
     * returns Dotpay office address
     * @return string
     */
    public function getOfficeIp()
    {
        return '77.79.195.34';
    }
    
    public function getDotpayEnabledFN()
    {
        return 'DP_GATEWAY_EN';
    }
    public function setDotpayEnabled($enable)
    {
        Configuration::updateValue($this->getDotpayEnabledFN(), $enable);
        return $this;
    }
    public function isDotpayEnabled()
    {
        return Configuration::get($this->getDotpayEnabledFN());
    }
    
    public function getDotpayApiVersionFN()
    {
        return 'DP_API_VERSION';
    }
    public function setDotpayApiVersion($apiVersion)
    {
        Configuration::updateValue($this->getDotpayApiVersionFN(), $apiVersion);
        return $this;
    }
    public function getDotpayApiVersion()
    {
        return Configuration::get($this->getDotpayApiVersionFN());
    }
    
    public function getDotpayRenewFN()
    {
        return 'DP_RENEW';
    }
    public function setDotpayRenew($renew)
    {
        Configuration::updateValue($this->getDotpayRenewFN(), $renew);
        return $this;
    }
    public function isDotpayRenewEn()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayRenewFN());
    }
    
    public function getDotpayRenewDaysFN()
    {
        return 'DP_RENEW_DAYS';
    }
    public function setDotpayRenewDays($renewDays)
    {
        Configuration::updateValue($this->getDotpayRenewDaysFN(), $renewDays);
        return $this;
    }
    public function getDotpayRenewDays()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayRenewDaysFN());
    }
    
    public function getDotpayRefundFN()
    {
        return 'DP_REFUND_EN';
    }
    public function setDotpayRefund($refund)
    {
        Configuration::updateValue($this->getDotpayRefundFN(), $refund);
        return $this;
    }
    public function isDotpayRefundEn()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayRefundFN());
    }
    
    public function getDotpayTestModeFN()
    {
        return 'DP_TEST_ENV';
    }
    public function setDotpayTestMode($test)
    {
        Configuration::updateValue($this->getDotpayTestModeFN(), $test);
        return $this;
    }
    public function isDotpayTestMode()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayTestModeFN());
    }
    
    public function getDotpayIdFN()
    {
        return 'DP_USER_ID';
    }
    public function setDotpayId($id)
    {
        Configuration::updateValue($this->getDotpayIdFN(), $id);
        return $this;
    }
    public function getDotpayId()
    {
        return Configuration::get($this->getDotpayIdFN());
    }
    

    public function getDotpayPINFN()
    {
        return 'DP_USER_PIN';
    }
    public function setDotpayPIN($pin)
    {
        Configuration::updateValue($this->getDotpayPINFN(), $pin);
        return $this;
    }
    public function getDotpayPIN()
    {
        return Configuration::get($this->getDotpayPINFN());
    }



   /**
    * Carrier options
    */ 
    
    public function Carrrieridprefix()
    {
        $Carrrier_id_prefix ='CarrierDotpay_';

        return $Carrrier_id_prefix;
    }





    public function getCarrierNoneFN()
    {
        return '';
    }

    public function getCarrierPointDeliveryFN()
    {
        return 'PICKUP_POINT';
    }

    public function getCarrierShopAtPlaceFN()
    {
        return 'PICKUP_SHOP';
    }


    public function getCarrierParcelRuchFN()
    {
        return 'PACZKA_W_RUCHU';
    }

    public function getCarrierParcelLockFN()
    {
        return 'PACZKOMAT';
    }


    public function getCarrierCounterFN()
    {
        return 'COURIER';
    }


   
    public function getDotpayPostponedPaymentFN()
    {
        return 'DP_POSTPONED_PAYMENT';
    }
    public function setDotpayPostponedPayment($advancedMode)
    {
        Configuration::updateValue($this->getDotpayPostponedPaymentFN(), $advancedMode);
        return $this;
    }
    public function isDotpayPostponedPayment()
    {
        return Configuration::get($this->getDotpayPostponedPaymentFN());
    }



    public function getDotpayWidgetModeFN()
    {
        return 'DP_WIDGET_MODE';
    }
    public function setDotpayWidgetMode($wm)
    {
        Configuration::updateValue($this->getDotpayWidgetModeFN(), $wm);
        return $this;
    }
    public function isDotpayWidgetMode()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayWidgetModeFN());
    }
    public function getDotpayAdvancedModeFN()
    {
        return 'DP_ADVANCED_MODE';
    }
    public function setDotpayAdvancedMode($advancedMode)
    {
        Configuration::updateValue($this->getDotpayAdvancedModeFN(), $advancedMode);
        return $this;
    }
    public function isDotpayAdvancedMode()
    {
        return Configuration::get($this->getDotpayAdvancedModeFN());
    }
    
    public function getDotpayDispInstructionFN()
    {
        return 'DP_DISP_INSTRUCTION';
    }
    public function setDotpayDispInstruction($dispInstruction)
    {
        Configuration::updateValue($this->getDotpayDispInstructionFN(), $dispInstruction);
        return $this;
    }
    public function isDotpayDispInstruction()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayDispInstructionFN());
    }
    
    public function getDotpayWidgetDisCurrFN()
    {
        return 'DP_WIDGET_DIS_CURR';
    }
    
    public function getDotpayChannelNameVisiblityFN()
    {
        return 'DP_WIDGET_DIS_CHANNELS_NAME';
    }
  

    public function isDotpayWidgetChannelsName()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayChannelNameVisiblityFN());
    }
  
    public function setDotpayWidgetDisCurr($disableCurrencies)
    {
        Configuration::updateValue($this->getDotpayWidgetDisCurrFN(), $disableCurrencies);
        return $this;
    }
    public function getDotpayWidgetDisCurr()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayWidgetDisCurrFN());
    }
    
    public function getDotpayMasterPassFN()
    {
        return 'DP_MP_MODE';
    }
    public function setDotpayMasterPass($mp)
    {
        Configuration::updateValue($this->getDotpayMasterPassFN(), $mp);
        return $this;
    }
    public function isDotpayMasterPass()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayMasterPassFN());
    }

    public function getDotpayPayPoFN()
    {
        return 'DP_PAYPO_MODE';
    }
    public function setDotpayPayPo($paypo)
    {
        Configuration::updateValue($this->getDotpayPayPoFN(), $paypo);
        return $this;
    }
    public function isDotpayPaypo()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayPayPoFN());
    }



    
    public function getDotpayBlikFN()
    {
        return 'DP_BLIK_MODE';
    }
    public function setDotpayBlik($blik)
    {
        Configuration::updateValue($this->getDotpayBlikFN(), $blik);
        return $this;
    }
    public function isDotpayBlik()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayBlikFN());
    }
    
    public function getDotpayOneClickFN()
    {
        return 'DP_ONECLICK_MODE';
    }
    public function setDotpayOneClick($oc)
    {
        Configuration::updateValue($this->getDotpayOneClickFN(), $oc);
        return $this;
    }
    public function isDotpayOneClick()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayOneClickFN());
    }
    
    public function getDotpayCreditCardFN()
    {
        return 'DP_CREDIT_CARD_MODE';
    }
    public function setDotpayCreditCard($cc)
    {
        Configuration::updateValue($this->getDotpayCreditCardFN(), $cc);
        return $this;
    }
    public function isDotpayCreditCard()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayCreditCardFN());
    }
    
    public function getDotpayPVFN()
    {
        return 'DP_PV_MODE';
    }
    public function setDotpayPV($pv)
    {
        Configuration::updateValue($this->getDotpayPVFN(), $pv);
        return $this;
    }
    public function isDotpayPV()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayPVFN());
    }
    
    public function getDotpayPvIdFN()
    {
        return 'DP_PV_ID';
    }
    public function setDotpayPvId($id)
    {
        Configuration::updateValue($this->getDotpayPvIdFN(), $id);
        return $this;
    }
    public function getDotpayPvId()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayPvIdFN());
    }
    
    public function getDotpayPvPINFN()
    {
        return 'DP_PV_PIN';
    }
    public function setDotpayPvPIN($pin)
    {
        Configuration::updateValue($this->getDotpayPvPINFN(), $pin);
        return $this;
    }
    public function getDotpayPvPIN()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayPvPINFN());
    }
    
    public function getDotpayPvCurrenciesFN()
    {
        return 'DP_PV_CUR';
    }
    public function setDotpayPvCurrencies($currencies)
    {
        Configuration::updateValue($this->getDotpayPvCurrenciesFN(), $currencies);
        return $this;
    }
    public function getDotpayPvCurrencies()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayPvCurrenciesFN());
    }
    
    public function getDotpayExChFN()
    {
        return 'DP_EXCH_EN';
    }
    public function setDotpayExCh($excharge)
    {
        Configuration::updateValue($this->getDotpayExChFN(), $excharge);
        return $this;
    }
    public function getDotpayExCh()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayExChFN());
    }
    
    public function setDotpayExchVPid($id)
    {
        Configuration::updateValue('PAYMENT_DP_NEW_VPRODUCT_PAYMENT', $id);
        return $this;
    }
    public function getDotpayExchVPid()
    {
        return Configuration::get('PAYMENT_DP_NEW_VPRODUCT_PAYMENT');
    }
    
    public function getDotpayExPercentageFN()
    {
        return 'DP_EXCH_PERC';
    }
    public function setDotpayExPercentage($percentage)
    {
        Configuration::updateValue($this->getDotpayExPercentageFN(), $percentage);
        return $this;
    }
    public function getDotpayExPercentage()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '0';
        }
        return Configuration::get($this->getDotpayExPercentageFN());
    }
    
    public function getDotpayExAmountFN()
    {
        return 'DP_EXCH_AM';
    }
    public function setDotpayExAmount($amount)
    {
        Configuration::updateValue($this->getDotpayExAmountFN(), $amount);
        return $this;
    }
    public function getDotpayExAmount()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '0';
        }
        return Configuration::get($this->getDotpayExAmountFN());
    }
    
    public function getDotpayDiscountFN()
    {
        return 'DP_DISC_EN';
    }
    public function setDotpayDiscount($discount)
    {
        Configuration::updateValue($this->getDotpayDiscountFN(), $discount);
        return $this;
    }
    public function getDotpayDiscount()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return false;
        }
        return Configuration::get($this->getDotpayDiscountFN());
    }
    
    public function setDotpayDiscountId($id)
    {
        Configuration::updateValue('PAYMENT_DP_NEW_DISCOUNT_PAYMENT', $id);
        return $this;
    }
    public function getDotpayDiscountId()
    {
        $id = Configuration::get('PAYMENT_DP_NEW_DISCOUNT_PAYMENT');
        return ($id===false)?null:$id;
    }

    public function getDotpayDiscAmountFN()
    {
        return 'DP_DISC_AM';
    }
    public function setDotpayDiscAmount($amount)
    {
        Configuration::updateValue($this->getDotpayDiscAmountFN(), $amount);
        return $this;
    }
    public function getDotpayDiscAmount()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '0';
        }
        return Configuration::get($this->getDotpayDiscAmountFN());
    }

    public function getDotpayDiscPercentageFN()
    {
        return 'DP_DISC_PERC';
    }
    public function setDotpayDiscPercentage($percentage)
    {
        Configuration::updateValue($this->getDotpayDiscPercentageFN(), $percentage);
        return $this;
    }
    public function getDotpayDiscPercentage()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '0';
        }
        return Configuration::get($this->getDotpayDiscPercentageFN());
    }
    
    public function getDotpayApiUsernameFN()
    {
        return 'DP_DISC_API_UNAME';
    }
    public function setDotpayApiUsername($username)
    {
        Configuration::updateValue($this->getDotpayApiUsernameFN(), $username);
        return $this;
    }
    public function getDotpayApiUsername()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayApiUsernameFN());
    }
    
    public function getDotpayApiPasswordFN()
    {
        return 'DP_DISC_API_PASSWD';
    }
    public function setDotpayApiPassword($password)
    {
        Configuration::updateValue($this->getDotpayApiPasswordFN(), $password);
        return $this;
    }
    public function getDotpayApiPassword()
    {
        if ($this->getDotpayApiVersion() === 'legacy') {
            return '';
        }
        return Configuration::get($this->getDotpayApiPasswordFN());
    }
    
    public function setDotpayNewStatusId($id)
    {
        return Configuration::updateValue('PAYMENT_DP_NEW_ORDER_STATUS', $id);
    }
    public function getDotpayNewStatusId()
    {
        $id = Configuration::get('PAYMENT_DP_NEW_ORDER_STATUS');
        return ($id===false)?null:$id;
    }
    
    public function setDotpayTotalRefundStatusId($id)
    {
        return Configuration::updateValue('PAYMENT_DP_TOTAL_REFUND_STATUS', $id);
    }
    public function getDotpayTotalRefundStatusId()
    {
        $id = Configuration::get('PAYMENT_DP_TOTAL_REFUND_STATUS');
        return ($id===false)?null:$id;
    }
    
    public function setDotpayPartialRefundStatusId($id)
    {
        return Configuration::updateValue('PAYMENT_DP_PARTIAL_REFUND_STATUS', $id);
    }
    public function getDotpayPartialRefundStatusId()
    {
        $id = Configuration::get('PAYMENT_DP_PARTIAL_REFUND_STATUS');
        return ($id===false)?null:$id;
    }
    
    public function setDotpayWaitingRefundStatusId($id)
    {
        return Configuration::updateValue('PAYMENT_DP_WAITING_REFUND_STATUS', $id);
    }
    public function getDotpayWaitingRefundStatusId()
    {
        $id = Configuration::get('PAYMENT_DP_WAITING_REFUND_STATUS');
        return ($id===false)?null:$id;
    }
    
    public function setDotpayFailedRefundStatusId($id)
    {
        return Configuration::updateValue('PAYMENT_DP_FAILED_REFUND_STATUS', $id);
    }
    public function getDotpayFailedRefundStatusId()
    {
        $id = Configuration::get('PAYMENT_DP_FAILED_REFUND_STATUS');
        return ($id===false)?null:$id;
    }
    
    /**
     * Check if account configuration is correct
     * @return bool
     */
    public function isApiConfigOk()
    {
        return ($this->getDotpayApiUsername()!='' && $this->getDotpayApiPassword()!='');
    }

    /**
     * Check if account configuration is correct
     * @return bool
     */
    public function isAccountConfigOk()
    {
        $pin = $this->getDotpayPIN();
        return (!empty($pin) && $this->checkId());
    }
    
    /**
     * 
     * @return array
     */
    public function getDotpayAvailableCurrency()
    {
        return array( 'PLN', 'EUR', 'USD', 'GBP', 'JPY', 'CZK', 'SEK', 'UAH', 'RON', 'NOK', 'BGN', 'CHF', 'HRK', 'HUF', 'RUB' );
    }
    
    /**
     * 
     * @return array
     */
    public function getDotpayAvailableLanguage()
    {
        return array( 'pl', 'en', 'de', 'it','fr', 'es', 'cz', 'hu','cs', 'ro', 'ru', 'uk' );
    }
    
    /**
     * Check, if Dotpay ID is correct
     * @return bool
     */
    private function checkId()
    {
        return (bool)preg_match("/^\d{5,6}$/", $this->getDotpayId());
    }
}
