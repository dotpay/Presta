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
    protected $customer_delivery;

    /**
     *
     * @var Address Object with customer address data
     */
    protected $address;
    protected $address_deliv;
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
     * @param $address_deliv, 1 - id_address_delivery, else - id_address_invoice
     * @return Address
     */
    public function getAddress($address_deliv = 0) {
        if ($this->address === null) {
            $this->address = new Address($this->getInitializedCart()->id_address_invoice);

        }
        if ($this->address_deliv === null) {
            $this->address_deliv = new Address($this->getInitializedCart()->id_address_delivery);

        }

        if($address_deliv == 1) {
            return $this->address_deliv;
        } else {
            return $this->address;
        }
    }

    /**
     * Returns customer object, created from correct source
     * @return Customer /billing address
     */

    public function getCustomer() {

        if ($this->customer === null) {
            $this->customer = new Customer($this->getInitializedCart()->id_customer);
        }

            return $this->customer;

    }


      /**
     * Returns customer object, created from correct source
     * @return Customer /delivery address
     */

    public function getCustomerDeliv() {

       $cart = $this->context->cart;

        $addressDeliveryId = $cart->id_address_delivery;
        $deliveryddress = new AddressCore($addressDeliveryId);

        if ($this->customer_delivery === null) {
                $this->customer_delivery = $deliveryddress;
        }

            return $this->customer_delivery;
    }

    /**
     * Returns date customer registered account
     * @return string, format 'Y-m-d'
     */
    public function getregisteredCustomerDate() {

            $date = $this->getCustomerDeliv()->date_add;
            $format = "Y-m-d H:i:s";

            if(date($format, strtotime($date)) == date($date)) {

                $date2 = DateTime::createFromFormat('Y-m-d H:i:s', $date);

              return $date2->format('Y-m-d');

            } else {

                return null;
            }

    }

    /**
     * Returns number of all orders for customer since his registration
     * @return int
     */
    public function getCustomerOrdersCount() {

            $customer_id = $this->getCustomer()->id;
            $orders = Order::getCustomerOrders($customer_id ,true);

            $allOrders = count($orders);


            if(isset($allOrders)) {

              return (int)$allOrders;

            } else {

                return 0;
            }

    }


    /**
     * Returns total shipping cost from cart
     * @return shipping total
     */
    public function getShippingTotalCart()
    {

        return $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
    }


    public function getselectedCarrierMethodGroup()
    {
        $carrrier_id_sel = context::getContext()->cart->id_carrier;
        $name = $this->module->getchosenCarries($carrrier_id_sel);

        return $name;

    }

    /**
     * Returns data to 'customer' parameter
     * @return string encoded base64
     */
    public function PayerDatadostawaJsonBase64() {

                $customer = array (
                                 "payer" => array(
                                         "first_name" => $this->NewPersonName($this->getCustomer()->firstname),
                                         "last_name" => $this->NewPersonName($this->getCustomer()->lastname),
                                         "email" => $this->getDotEmail(),
                                         "phone" => $this->getDotPhone()
                                          ),
                                 "order" => array(
                                         "delivery_address" => array(

                                                           "city" => $this->getDotCity(1),
                                                           "street" => $this->getDotStreetAndStreetN1(1)['street'],
                                                           "building_number" => $this->getDotStreetAndStreetN1(1)['street_n1'],
                                                           "postcode" => $this->getDotPostcode(1),
                                                           "country" => $this->getDotCountry(1)
                                                                     )
                                            )

                                 );

                                 if ($this->getregisteredCustomerDate() != null)
                                 {
                                    $customer["registered_since"] = $this->getregisteredCustomerDate();
                                    $customer["order_count"] = $this->getCustomerOrdersCount();
                                 }

                                if ($this->getSelectedCarrierMethodGroup() != null) 
                                {
                                    $customer["order"]["delivery_type"] = $this->getSelectedCarrierMethodGroup();
                                }

                                $order = new Order(Order::getOrderByCartId($this->context->cart->id));

                                if ($this->getLastOrderNumber() !== null && $order->reference !== null)
                                {
                                   $customer["order"]["id"] = $order->reference.'/'.$this->getLastOrderNumber();
                                }


                                $customer_base64 = base64_encode(json_encode($customer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                            return $customer_base64;
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
        $this->checkOrderOwnership($this->context->customer->id, $order->id_customer);
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
     * Returns correct SERVER NAME or HOSTNAME
     * @return string
     */
    public function getHost()
    {

		$possibleHostSources = array('HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR');
		$sourceTransformations = array(
			"HTTP_X_FORWARDED_HOST" => function($value) {
				$elements = explode(',', $value);
				return trim(end($elements));
			}
		);
		$host = '';
		foreach ($possibleHostSources as $source)
		{
			if (!empty($host)) break;
			if (empty($_SERVER[$source])) continue;
			$host = $_SERVER[$source];
			if (array_key_exists($source, $sourceTransformations))
			{
				$host = $sourceTransformations[$source]($host);
			}
		}

		// Remove port number from host
		$host = preg_replace('/:\d+$/', '', $host);

		return trim($host);

    }

	 /**
	 * The validator checks if the given URL address is correct.
	 */
	public function validateHostname($value)
    {
        return (bool) preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,10}$/', $value);
    }

	 /**
     * replacing removing double or more special characters that appear side by side by space from: firstname, lastname, city, street, p_info...
     * @return string
     */
    public function replaceCharacters($originalValue)
		{
			$originalValue1 = preg_replace('/(\s{2,}|\.{2,}|@{2,}|\-{2,}|\/{2,} | \'{2,}|\"{2,}|_{2,})/', ' ', $originalValue);
			return trim($originalValue1);
		}

	/**
	 * checks and crops the size of a string
	 * the $special parameter means an estimate of how many urlencode characters can be used in a given field
	 * e.q. 'ż' (1 char) -> '%C5%BC' (6 chars)
	 */
	public function encoded_substrParams($string, $from, $to, $special=0)
		{
			$s = html_entity_decode($this->replaceCharacters($string),ENT_QUOTES, 'UTF-8');
			$sub = mb_substr($s, $from, $to,'UTF-8');
			$sum = strlen(urlencode($sub));

			if($sum  > $to)
				{
					$newsize = $to - $special;
					$sub = mb_substr($s, $from, $newsize,'UTF-8');
				}
			return trim($sub);
		}


	/**
	 * prepare data for the firstname and lastname so that it would be consistent with the validation
	 */
	public function NewPersonName($value)
		{
			$NewPersonName1 = preg_replace('/[^\p{L}0-9\s\-_]/u',' ',$value);
			return $this->encoded_substrParams($NewPersonName1,0,50,24);
		}



	/**
	 * prepare data for the city so that it would be consistent with the validation
	 */
	public function NewCity($value)
		{
			$NewCity1 = preg_replace('/[^\p{L}0-9\.\s\-\/_,]/u',' ',$value);
			return $this->encoded_substrParams($NewCity1,0,50,24);
		}


	/**
	 * prepare data for the street so that it would be consistent with the validation
	 */
	public function NewStreet($value)
		{
			$NewStreet1 = preg_replace('/[^\p{L}0-9\.\s\-\/_,]/u',' ',$value);
			return $this->encoded_substrParams($NewStreet1,0,100,50);
		}

	/**
	 * prepare data for the street_n1 so that it would be consistent with the validation
	 */
	public function NewStreet_n1($value)
		{
			$NewStreet_n1a = preg_replace('/[^\p{L}0-9\s\-_\/]/u',' ',$value);
			return $this->encoded_substrParams($NewStreet_n1a,0,30,24);
		}

	/**
	 * prepare data for the phone so that it would be consistent with the validation
	 */
	public function NewPhone($value)
		{
			$NewPhone1 = preg_replace('/[^\+\s0-9\-_]/','',$value);
			return $this->encoded_substrParams($NewPhone1,0,20,6);
		}


	/**
	 * prepare data for the postcode so that it would be consistent with the validation
	 */
	public function NewPostcode($value)
		{
			$NewPostcode1 = preg_replace('/[^\d\w\s\-]/','',$value);
			return $this->encoded_substrParams($NewPostcode1,0,20,6);
		}


    /**
     * Returns unique value for every order
     * @return string
     */
    public function getDotControl($source = null)
		{
			if ($source == null) {

				if ($this->validateHostname($this->getHost()))
					{
						$server_name = $this->getHost();
					} else {
						$server_name = "HOSTNAME";
					}

				$exAmount_is = $this->api->getExtrachargeAmount(true);
				 if ($exAmount_is  > 0) {
					 return $this->getLastOrderNumber().'|'.$server_name.'|PS16 module:'.$this->module->version.'|fee:'.$exAmount_is.' '.$this->getDotCurrency();
				 }else{
					return $this->getLastOrderNumber().'|'.$server_name.'|PS16 module:'.$this->module->version;
				 }

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
			$Shop_name = Configuration::get('PS_SHOP_NAME');
			$NewShop_name1 = preg_replace('/[^\p{L}0-9\s\"\/\\:\.\$\+!#\^\?\-_@]/u','',$Shop_name);
			return $this->encoded_substrParams($NewShop_name1,0,300,60);


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

			$exAmount_is = $this->api->getExtrachargeAmount(true);
			 if ($exAmount_is  > 0) {
				$exAmount_desc = " ".$this->module->l("(including an extra fee:").$exAmount_is." ".$this->getDotCurrency().")";
			 }else{
				$exAmount_desc = "";
			 }
			 $disAmount_is = $this->api->getDiscountAmount();
			 if ($disAmount_is  > 0) {
				$disAmount_desc = " ".$this->module->l("(including discount:")." -".$disAmount_is." ".$this->getDotCurrency().")";
			 }else{
				$disAmount_desc = "";
			 }


        if ($this->config->getDotpayApiVersion() == 'dev') {
            return ($this->module->l("Order ID:").' '.$order->reference.''.$exAmount_desc.$disAmount_desc);
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
    public function getDotFirstname($address_deliv = 0)
    {
        if($address_deliv == 1)
        {
            return $this->NewPersonName($this->getCustomerDeliv()->firstname);
        } else {
            return $this->NewPersonName($this->getCustomer()->firstname);
        }

    }

    /**
     * Returns lastname of customer
     * @return string
     */
    public function getDotLastname($address_deliv = 0)
    {
        if($address_deliv == 1)
        {
            return $this->NewPersonName($this->getCustomerDeliv()->lastname);
        } else {
            return $this->NewPersonName($this->getCustomer()->lastname);
        }
    }

    /**
     * Returns email of customer
     * @return string
     */
    public function getDotEmail($address_deliv = 0)
    {
		$email = $this->getCustomer()->email;

		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
          $Newemail = filter_var($email,FILTER_SANITIZE_EMAIL);
        } else {
		  $Newemail = $email;
		}

        return $Newemail;
    }

    /**
     * Returns phone of customer
     * @return string
     */
    public function getDotPhone($address_deliv = 0)
    {
        if($address_deliv == 1) {
            $address = $this->getCustomerDeliv();
        } else {
            $address = $this->getAddress();
        }

        $phone = '';
        if ($address->phone != '') {
            $phone = $this->NewPhone($address->phone);
        } else if ($address->phone_mobile != '') {
            $phone = $this->NewPhone($address->phone_mobile);
        }
        return $phone;
    }

    /**
     * Returns street and building number even if customer didn't get a value of building number
     * @return array
     */
    public function getDotStreetAndStreetN1($address_deliv = 0)
    {
        if($address_deliv == 1) {
            $address = $this->getCustomerDeliv();
        } else {
            $address = $this->getAddress();
        }

        $streetA = $address->address1;
        $street = $this->NewStreet($streetA);

        $street_n1A = $address->address2;
        $street_n1 = $this->NewStreet_n1($street_n1A);

        if (empty($street_n1)) {
            preg_match("/\s[\p{L}0-9\s\-_\/]{1,15}$/u", $street, $matches);
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
    public function getDotCity($address_deliv = 0)
    {
        if($address_deliv == 1) {
            return $this->NewCity($this->getCustomerDeliv()->city); //delivery address
        } else {
            return $this->NewCity($this->getAddress()->city);  //invoices address
        }

    }

    /**
     * Returns a postcode of customer
     * @return string
     */
    public function getDotPostcode($address_deliv = 0)
    {
        if($address_deliv == 1) {
            return $this->NewPostcode($this->getCustomerDeliv()->postcode);
        } else {
            return $this->NewPostcode($this->getAddress()->postcode);
        }

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
    public function getDotCountry($address_deliv = 0)
    {
        $country = new Country((int)($this->getAddress()->id_country));
        $country_delivery = new Country((int)($this->getCustomerDeliv()->id_country));

        if($address_deliv == 1) {
            return $country_delivery->iso_code;
        } else {
            return $country->iso_code;
        }
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
     * Returns an URL to MasterPass channel logo
     * @return string
     */
    public function getDotPayPoLogo()
    {
        return $this->module->getPath().'views/img/PayPo.png';
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

    /**
     * Check if customer, who is set in context, is assigned to the order
     * @param int $customerId If of customer which is set in context
     * @param int $orderOwnerId Id of customer which is assigned to the order
     */
    protected function checkOrderOwnership($customerId, $orderOwnerId)
    {
        if ((int)$customerId !== (int)$orderOwnerId) {
            die($this->module->l('An error with ownership of this order occured'));
        }
    }
}
