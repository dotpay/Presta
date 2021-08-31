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

require_once(DOTPAY_PLUGIN_DIR.'/classes/Curl.php');

/**
 * Provides the functionality of seller API
 */
class DotpaySellerApi
{
    private $baseurl;
    
    public function __construct($url)
    {
        $this->baseurl = $url;
    }
    
    /**
     * Returns infos about credit card
     * @param string $username
     * @param string $password
     * @param string $number
     * @return \stdClass
     */
    public function getCreditCardInfo($username, $password, $number)
    {
        $payment = $this->getPaymentByNumber($username, $password, $number);
        if ($payment->payment_method->channel_id!=248) {
            return null;
        }
        return $payment->payment_method->credit_card;
    }
    
    /**
     * Checks, if username and password are right
     * @param string $username Username of user
     * @param string $password Password of user
     * @param string $version Version of used Dotpay Api
     * @return boolean|null
     */
    public function isAccountRight($username, $password, $version)
    {
        if ($version == 'legacy') {
            return null;
        }
        if (empty($username) && empty($password)) {
            return null;
        }
        $url = $this->baseurl.$this->getDotPaymentApi()."payments/";
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $curl->exec();
        $info = $curl->getInfo();
        return ($info['http_code']>=200 && $info['http_code']<300);
    }
    
    /**
     * Checks if seller's PIN is right
     * @param string $username Username of user
     * @param string $password Password of user
     * @param string $version Version of used Dotpay Api
     * @param int $id Seller ID
     * @param string $pin Seller PIN
     * @return boolean|null
     */
    public function isSellerPinOk($username, $password, $version, $id, $pin)
    {
        if ($version == 'legacy') {
            return null;
        }
        if (empty($username) && empty($password)) {
            return null;
        }
        $url = $this->baseurl.$this->getDotPaymentApi()."accounts/$id/?format=json";
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $account = json_decode($curl->exec(), true);
        if (!isset($account['config'])) {
            return null;
        }
        return ($account['config']['pin'] == $pin);
    }
    
    /**
     * Returns ifnos about payment
     * @param string $username
     * @param string $password
     * @param string $number
     * @return \stdClass
     */
    public function getPaymentByNumber($username, $password, $number)
    {
        $url = $this->baseurl.$this->getDotPaymentApi()."payments/$number/";
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = Tools::jsonDecode($curl->exec());
        return $response;
    }
    
    /**
     * Returns infos about payment
     * @param string $username
     * @param string $password
     * @param int $orderId
     * @return \stdClass
     */
    public function getPaymentByOrderId($username, $password, $orderId)
    {
        $url = $this->baseurl.$this->getDotPaymentApi().'payments/?control='.$orderId;
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password);
        $this->setCurlOption($curl);
        $response = Tools::jsonDecode($curl->exec());
        return $response->results;
    }
    
    /**
     * Makes a return payment and returns infos about a result of this operation
     * @param string $username
     * @param string $password
     * @param string $payment
     * @param float $amount
     * @param type $control
     * @param type $description
     * @return type
     */
    public function makeReturnMoney($username, $password, $payment, $amount, $control, $description)
    {
        $url = $this->baseurl.$this->getDotPaymentApi().'payments/'.$payment.'/refund/';
        $data = array(
            'amount' => str_replace(',', '.', $amount),
            'description' => $description,
            'control' => $control
        );
		
        if (function_exists('json_encode')) {
            $dataPost = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $dataPost = Tools::jsonEncode($data);
        }	
		
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url)
             ->addOption(CURLOPT_USERPWD, $username.':'.$password)
             ->addOption(CURLOPT_POST, 1)
             ->addOption(CURLOPT_POSTFIELDS, $dataPost)
             ->addOption(CURLOPT_SSL_VERIFYPEER, true)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_TIMEOUT, 100)
             ->addOption(CURLOPT_HTTPHEADER, array(
                'Accept: application/json; indent=4',
                'content-type: application/json',
                'User-Agent: DotpayModule_v2.5.0 Prestashop 1.6 - refund'));
        $resp = Tools::jsonDecode($curl->exec(), true);
        
        return ($curl->getInfo() + $resp);

    }
    
    /**
     * Returns path for payment API
     * @return string
     */
    private function getDotPaymentApi()
    {
        return "api/v1/";
    }

    /**
     * Sets option for cUrl and return cUrl resource
     * @param resource $curl
     */
    private function setCurlOption($curl)
    {
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_TIMEOUT, 100)
             ->addOption(CURLOPT_CUSTOMREQUEST, "GET");
    }
}
