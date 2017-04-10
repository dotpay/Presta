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

require_once(DOTPAY_PLUGIN_DIR.'/models/Config.php');

/**
 * Abstract controller for other Dotpay plugin controllers
 */
abstract class DotpayController extends ModuleFrontController
{
    /**
     *
     * @var DotpayConfig Dotpay configuration 
     */
    protected $config;
    
    /**
     *
     * @var Customer Object with customer data
     */
    protected $customer;
    
    /**
     *
     * @var Address Object with customer address data
     */
    protected $address;
    
    /**
     *
     * @var DotpayApi Api for selected Dotpay payment API (dev or legacy)
     */
    protected $api;
    
    /**
     *
     * @var float Total amount of order or cart, which is used to payment
     */
    protected $totalAmount;
    
    /**
     *
     * @var float Shipping amount of order or cart, which is used to payment
     */
    protected $shippingAmount;
    
    /**
     *
     * @var int Id of currency, which is used to payment
     */
    protected $currencyId;
    
    /**
     * Prepares environment for all Dotpay controllers
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = new DotpayConfig();
        
        if ($this->config->getDotpayApiVersion()=='legacy') {
            $this->api = new DotpayLegacyApi($this);
        } else {
            $this->api = new DotpayDevApi($this);
        }
        
        $this->module->registerFormHelper();
    }
    
    /**
     * Returns address object, created from correct source
     * @return Address
     */
    public function getAddress() {
        if ($this->address === null) {
            $this->address = new Address($this->getInitializedCart()->id_address_invoice);
        }
        return $this->address;
    }
    
    /**
     * Returns customer object, created from correct source
     * @return Customer
     */
    public function getCustomer() {
        if ($this->customer === null) {
            $this->customer = new Customer($this->getInitializedCart()->id_customer);
        }
        return $this->customer;
    }
    
    /**
     * Returns currency id, came from correct source
     * @return int
     */
    public function getCurrencyId() {
        if ($this->currencyId === null) {
            $this->currencyId = $this->getInitializedCart()->id_currency;
        }
        return $this->currencyId;
    }
    
    /**
     * Sets the given order as a source of a data for payment
     * @param int $orderId Id of order
     * @param bool $force Flag if setting can be done without chacking if it's allowed
     */
    public function setOrderAsSource($orderId, $force = false) {
        $order = new Order($orderId);
        if ($force || $this->module->ifRenewActiveForOrder($order)) {
            $this->totalAmount = $order->total_paid;
            $this->shippingAmount = $order->total_shipping;
            foreach($order->getBrother() as $order) {
                $this->totalAmount += $order->total_paid;
                $this->shippingAmount += $order->total_shipping;
            }
            $this->currencyId = $order->id_currency;
        } else {
            die($this->module->l('You can not renew your payment, because this possibility has expired for your order.'));
        }
    }
    
    /**
     * Returns seller ID
     * @return string
     */
    public function getDotId()
    {
        return $this->config->getDotpayId();
    }
    
    /**
     * Returns last order number
     * @return string
     */
    public function getLastOrderNumber()
    {
        return Order::getOrderByCartId($this->context->cart->id);
    }

    /**
     * Returns unique value for every order
     * @return string
     */
    public function getDotControl($source = null)
    {
        if ($source == null) {
            return $this->getLastOrderNumber().'|'.$_SERVER['SERVER_NAME'].'|module:'.$this->module->version;
        } else {
            $tmp = explode('|', $source);
            return $tmp[0];
        }
    }
    
    /**
     * Returns title of shop
     * @return string
     */
    public function getDotPinfo()
    {
        return Configuration::get('PS_SHOP_NAME');
    }
    
    /**
     * Returns amount of order
     * @return float
     */
    public function getDotAmount()
    {
        if ($this->totalAmount === null) {
            $this->totalAmount = $this->getInitializedCart()->getOrderTotal(true, Cart::BOTH);
        }
        if ($this->currencyId === null) {
            $this->currencyId = $this->context->cart->id_currency;
        }
        return $this->api->getFormatAmount($this->totalAmount);
    }
    
    /**
     * Returns amount of shipping
     * @return float
     */
    public function getDotShippingAmount()
    {
        if ($this->shippingAmount === null) {
            $this->shippingAmount = $this->getInitializedCart()->getOrderTotal(true, Cart::ONLY_SHIPPING);
        }
        if ($this->currencyId === null) {
            $this->currencyId = $this->context->cart->id_currency;
        }
        return $this->api->getFormatAmount($this->shippingAmount);
    }
    
    /**
     * Returns code of currency used in order
     * @return string
     */
    public function getDotCurrency()
    {
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        return $currency["iso_code"];
    }
    
    /**
     * Returns id of order currency
     * @return int
     */
    public function getDotCurrencyId()
    {
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        return $currency["id_currency"];
    }
    
    /**
     * Returns description of order
     * @return string
     */
    public function getDotDescription()
    {
        $order = new Order(Order::getOrderByCartId($this->context->cart->id));
        if ($this->config->getDotpayApiVersion() == 'dev') {
            return ($this->module->l("Order ID:").' '.$order->reference);
        } else {
            return ($this->module->l("Your order ID:").' '.$order->reference);
        }
    }
    
    /**
     * Returns language code for customer language
     * @return string
     */
    public function getDotLang()
    {
        $lang = Tools::strtolower(LanguageCore::getIsoById($this->context->cookie->id_lang));
        if (in_array($lang, $this->config->getDotpayAvailableLanguage())) {
            return $lang;
        } else {
            return "en";
        }
    }
    
    /**
     * Returns name of server protocol, using by shop
     * @return string
     */
    public function getServerProtocol()
    {
        $result = 'http';
        
        if ($this->module->isSSLEnabled()) {
            $result = 'https';
        }
        
        return $result;
    }
    
    /**
     * Returns URL of site where Dotpay redirect after payment
     * @return string
     */
    public function getDotUrl()
    {
        return $this->context->link->getModuleLink('dotpay', 'back', array('orderId' => Order::getOrderByCartId($this->context->cart->id)), $this->module->isSSLEnabled());
    }
    
    /**
     * Returns URL of site where Dotpay send URLC confirmations
     * @return string
     */
    public function getDotUrlC()
    {
        return $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), $this->module->isSSLEnabled());
    }
    
    /**
     * Returns firstname of customer
     * @return string
     */
    public function getDotFirstname()
    {
        return $this->getCustomer()->firstname;
    }
    
    /**
     * Returns lastname of customer
     * @return string
     */
    public function getDotLastname()
    {
        return $this->getCustomer()->lastname;
    }
    
    /**
     * Returns email of customer
     * @return string
     */
    public function getDotEmail()
    {
        return $this->getCustomer()->email;
    }
    
    /**
     * Returns phone of customer
     * @return string
     */
    public function getDotPhone()
    {
        $address = $this->getAddress();
        $phone = '';
        if ($address->phone != '') {
            $phone = $address->phone;
        } else if ($address->phone_mobile != '') {
            $phone = $address->phone_mobile;
        }
        return $phone;
    }
    
    /**
     * Returns street and building number even if customer didn't get a value of building number
     * @return array
     */
    public function getDotStreetAndStreetN1()
    {
        $address = $this->getAddress();
        $street = $address->address1;
        $street_n1 = $address->address2;
        if (empty($street_n1)) {
            preg_match("/\s[\w\d\/_\-]{0,30}$/", $street, $matches);
            if (count($matches)>0) {
                $street_n1 = trim($matches[0]);
                $street = str_replace($matches[0], '', $street);
            }
        }
        return array(
            'street' => $street,
            'street_n1' => $street_n1
        );
    }
    
    /**
     * Returns a city of customer
     * @return string 
     */
    public function getDotCity()
    {
        return $this->getAddress()->city;
    }
    
    /**
     * Returns a postcode of customer
     * @return string 
     */
    public function getDotPostcode()
    {
        return $this->getAddress()->postcode;
    }
    
    /**
     * Checks if PV card channel for separated currencies is enabled
     * @return boolean
     */
    public function isDotpayPVEnabled()
    {
        $result = $this->config->isDotpayPV();
        if (!$this->isDotSelectedCurrency($this->config->getDotpayPvCurrencies())) {
            $result = false;
        }
        return $result;
    }
    
    /**
     * Checks if main channel is enabled
     * @return boolean
     */
    public function isMainChannelEnabled()
    {
        if ($this->isDotSelectedCurrency($this->config->getDotpayWidgetDisCurr())) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns a country of customer
     * @return string 
     */
    public function getDotCountry()
    {
        $country = new Country((int)($this->getAddress()->id_country));
        return $country->iso_code;
    }
    
    /**
     * Returns an URL to Blik channel logo
     * @return string
     */
    public function getDotBlikLogo()
    {
        return $this->module->getPath().'views/img/BLIK.png';
    }
    
    /**
     * Returns an URL to MasterPass channel logo
     * @return string
     */
    public function getDotMasterPassLogo()
    {
        return $this->module->getPath().'views/img/MasterPass.png';
    }
    
    /**
     * Returns an URL to One click card channel logo
     * @return string
     */
    public function getDotOneClickLogo()
    {
        return $this->module->getPath().'views/img/oneclick.png';
    }
    
    /**
     * Returns an URL to PV card channel logo
     * @return string
     */
    public function getDotPVLogo()
    {
        return $this->module->getPath().'views/img/oneclick.png';
    }
    
    /**
     * Returns an URL to card channel logo
     * @return string
     */
    public function getDotCreditCardLogo()
    {
        return $this->module->getPath().'views/img/oneclick.png';
    }
    
    /**
     * Returns an URL to main channel logo
     * @return string
     */
    public function getDotpayLogo()
    {
        return $this->module->getPath().'views/img/dotpay.png';
    }
    
    /**
     * Returns URL of site where is creating an request to Dotpay
     * @return string
     */
    public function getPreparingUrl()
    {
        return $this->context->link->getModuleLink($this->module->name, 'preparing', array(), $this->module->isSSLEnabled());
    }
    
    /**
     * Init personal data about cart, customer adn adress
     */
    public function getInitializedCart()
    {
        if ($this->context->cart==null) {
            $this->context->cart = new Cart($this->context->cookie->id_cart);
        }
        return $this->context->cart;
    }
    
    /**
     * Checks, if given currenncy is on the given list, if none of pcurrencies is given as an argument, then it's got from current order settings
     * @param array $allowCurrencyForm
     * @param string|null $paymentCurrency
     * @return boolean
     */
    public function isDotSelectedCurrency($allowCurrencyForm, $paymentCurrency = null)
    {
        $result = false;
        if ($paymentCurrency==null) {
            $paymentCurrency = $this->getDotCurrency();
        }
        $allowCurrency = str_replace(';', ',', $allowCurrencyForm);
        $allowCurrency = Tools::strtoupper(str_replace(' ', '', $allowCurrency));
        $allowCurrencyArray =  explode(",", trim($allowCurrency));
        
        if (in_array(Tools::strtoupper($paymentCurrency), $allowCurrencyArray)) {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Check, if Virtual Product from Dotpay additional payment is in card
     * @return boolean
     */
    protected function isExVPinCart()
    {
        $products = $this->getInitializedCart()->getProducts(true);
        foreach ($products as $product) {
            if ($product['id_product'] == $this->config->getDotpayExchVPid()) {
                return true;
            }
        }
        return false;
    }
}
