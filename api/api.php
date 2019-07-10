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
require_once(DOTPAY_PLUGIN_DIR.'/controllers/front/dotpay.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/Curl.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/SellerApi.php');

/**
 * Interface and common functionality of the API.
 */
abstract class DotpayApi
{
    /**
     *
     * @var int Number of One Click card channel
     */
    public static $ocChannel = 248;
    
    /**
     *
     * @var int Number of card channel for separated currencies
     */
    public static $pvChannel = 248;
    
    /**
     *
     * @var int Number of card channel
     */
    public static $ccChannel = 246;
    
    /**
     *
     * @var int Number of Blik channel
     */
    public static $blikChannel = 73;
    
    /**
     *
     * @var int Number of MasterPass channel
     */
    public static $mpChannel = 71;

    /**
     *
     * @var int Number of PayPo channel
     */
    public static $paypoChannel = 95;


    /**
     *
     * @var int Minimum amount for PayPo channel
     */
    public static $paypoChannelamountmin = 40;

    /**
     *
     * @var int Maximum amount for PayPo channel
     */
    public static $paypoChannelamountmax = 1000;



    /**
     *
     * @var DotpayController Controller object 
     */
    protected $parent;
    
    /**
     *
     * @var DotpayConfig DotpayConfig object
     */
    protected $config;

    /**
     *
     * Channels group for cash method 
     */
    const CASH_GROUP = 'cash';
    
    /**
     *
     * Channels group for transfers method
     */
    const TRANSFERS_GROUP = 'transfers';
    
    /**
     * Payment mode of operation
     */
    const PAYMENT_OPERATION = 'payment';
    
    /**
     * Refund mode of operation
     */
    const REFUND_OPERATION = 'refund';
    
    /**
     *
     * @var array Saved data about channels 
     */
    private $channelsData = array();
    
    /**
     *
     * @var type Ids of separated channels, which are added to a list on checkout page
     */
    private $enabledSeparatedChannels = array();
    
    /**
     * 
     * @param DotpayController $parent Owner of the object API.
     */
    public function __construct(DotpayController $parent = null)
    {
        $this->parent = $parent;
        $this->config = new DotpayConfig();
    }
    
    /**
     * Returns amount in correct format
     * @param float $amount
     * @return string
     */
    public function getFormatAmount($amount)
    {
        $currency = Currency::getCurrency(Context::getContext()->cart->id_currency);
        if (isset($currency['decimals']) && $currency['decimals']==0) {
            if (Configuration::get('PS_PRICE_ROUND_MODE')!=null) {
                switch (Configuration::get('PS_PRICE_ROUND_MODE')) {
                    case 0:
                        $amount = ceil($amount);
                        break;
                    case 1:
                        $amount = floor($amount);
                        break;
                    case 2:
                        $amount = round($amount);
                        break;
                }
            }
        }
        $amount = Tools::displayPrice($amount, $currency);
        return $this->fixAmountSeparator($amount);
    }
    
    /**
     * Fix separators in the given amount
     * @param string $inputAmount Input amount
     * @param string $separator Separator which should be removed besides the last one
     * @return type
     */
    protected function fixAmountSeparator($inputAmount, $separator = '.') {
        $amount = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $inputAmount));
        $part1 = str_replace($separator, '', substr($amount, 0, strrpos($amount, $separator)));
        $part2 = substr($amount, strrpos($amount, $separator));
        return $part1.$part2;
    }
    
    /**
     * Returns list of payment channels
     */
    abstract public function getChannelList();
    
    /**
     * Returns flag, if was selected PV channel
     */
    abstract public function isSelectedPvChannel();
    
    /**
     * Check confirm message from Dotpay
     */
    abstract public function checkConfirm();
    
    /**
     * Returns total amount from confirm message
     */
    abstract public function getTotalAmount();
    
    /**
     * Returns currency from confirm message
     */
    abstract public function getOperationCurrency();

    /**
     * Returns name of operation status
     */
    abstract public function getOperationStatusName();

    /**
     * Returns operation number from confirm message
     */
    abstract public function getOperationNumber();
    
    /**
     * Returns related operation number from confirm message
     */
    abstract public function getRelatedOperationNumber();
    
    /**
     * Returns new order state identifier from confirm message
     */
    abstract public function getNewOrderState($lastOrderState);
    
    /**
     * Returns hidden form for Dotpay Helper Form
     */
    abstract public function getHiddenForm();
    
    /**
     * Performs actions on preparing form
     */
    abstract public function onPrepareAction($action, $params);

    /**
     * Check, if channel is in channels groups
     */
    abstract public function isChannelInGroup($channelId, array $groups);
    
    /**
     * Returns CHK for request params
     */
    abstract protected function generateCHK($DotpayId, $DotpayPin, $ParametersArray);
    
    /**
     * Returns amount for extra charge
     */
    abstract public function getExtrachargeAmount();
    
    /**
     * Returns amount for discount for Dotpay
     */
    abstract public function getDiscountAmount();
    
    /**
     * Checks if seller account is right
     */
    abstract public function checkSellerId($sellerId);
	
	/**
     * Checks if test mode is enabled
     */
    abstract public function getinfoaboutTest();
	
    
    /**
     * Returns operation type
     */
    abstract public function getOperationType();
    
    /**
     * Returns array with enabled separated channels
     * @return array
     */
    public function getSeparatedChannelsList()
    {
        return $this->enabledSeparatedChannels;
    }
    
    /**
     * Returns header form for Dotpay Helper Form
     * @param string $formTarget
     * @param string|null $url
     * @return array
     */
    protected function getFormHeader($formTarget, $url = null)
    {
        if ($url == null) {
            $url = $this->config->getDotpayTargetUrl();
        }
        return array(
            'action' => $url,
            'method' => 'post',
            'form-target' => $formTarget,
            'class' => 'dotpay-form'
        );
    }
    
    /**
     * Returns a specific agreements
     * @param string $what
     * @return string
     */
    protected function getAgreements($what)
    {
        $resultStr = '';
        $result = $this->getApiChannels();
        if (false !== $result) {
            if (isset($result['forms']) && is_array($result['forms'])) {
                foreach ($result['forms'] as $forms) {
                    if (isset($forms['fields']) && is_array($forms['fields'])) {
                        foreach ($forms['fields'] as $forms1) {
                            if ($forms1['name'] == $what) {
                                $resultStr = $forms1['description_html'];
                            }
                        }
                    }
                }
            }
        }
        return $resultStr;
    }
    
    /**
     * Returns bylaw agreements
     * @return string
     */
    public function getByLaw()
    {
        $byLawAgreements = $this->getAgreements('bylaw');
        if (trim($byLawAgreements) == '') {
            $byLawAgreements = 'I accept Dotpay sp. z o.o. <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/t2/cloudfs1/magellan_media/regulations_of_payments">Regulations of Payments</a>.';
        }
        return $byLawAgreements;
    }
    
    /**
     * Returns personal data agreements
     * @return string
     */
    public function getPersonalData()
    {
        $personalDataAgreements = $this->getAgreements('personal_data');
        if (trim($personalDataAgreements) == '') {
            $personalDataAgreements = 'I acknowledge that in order to implement the payment process the Administrator of mine personal data is Dotpay sp. z o.o. (KRS 0000296790), 30-552 Kraków (Poland), Wielicka 28B, +48126882600, <a href="mailto:bok@dotpay.pl">bok@dotpay.pl</a>, see <a title="regulations of payments" target="_blank" href="https://ssl.dotpay.pl/t2/cloudfs1/magellan_media/rodo_en">the full text of the information clause</a>.';
        }
        return $personalDataAgreements;
    }


    /**
     * Returns channel data, if payment channel is active for order data
     * @param type $id channel id
     * @return array|false
     */
    public function getChannelData($id, $pv = false)
    {
        $result = $this->getApiChannels($pv);
        if (false != $result) {
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id']==$id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Returns string with channels data JSON
     * @return string|boolean
     */
    protected function getApiChannels($pv = false)
    {
        if (empty($this->channelsData[$pv])) {
            $this->channelsData[$pv] = $this->getApiChannelsFromServer($pv);
        }
        return $this->channelsData[$pv];
    }
    
    /**
     * Returns string with channels data JSON from Dotpay server
     * @return string|boolean
     */
    private function getApiChannelsFromServer($pv = false)
    {
        $dotpayUrl = $this->config->getDotpayTargetUrl();
        $paymentCurrency = $this->parent->getDotCurrency();
        
        if ($pv) {
            $dotpayId = $this->config->getDotpayPvId();
        } else {
            $dotpayId = $this->config->getDotpayId();
        }
        
        $orderAmount = $this->parent->getDotAmount();
        
        $dotpayLang = $this->parent->getDotLang();
        
        $curlUrl = "{$dotpayUrl}payment_api/channels/";
        $curlUrl .= "?currency={$paymentCurrency}";
        $curlUrl .= "&id={$dotpayId}";
        $curlUrl .= "&amount={$orderAmount}";
        $curlUrl .= "&lang={$dotpayLang}";
        
        try {
            $curl = new DotpayCurl();
            $curl->addOption(CURLOPT_SSL_VERIFYPEER, false)
                 ->addOption(CURLOPT_HEADER, false)
                 ->addOption(CURLOPT_URL, $curlUrl)
                 ->addOption(CURLOPT_REFERER, $curlUrl)
                 ->addOption(CURLOPT_RETURNTRANSFER, true);
            $resultJson = $curl->exec();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if ($curl) {
            $curl->close();
        }
        return Tools::jsonDecode($resultJson, true);
    }

    /**
     * Returns one hidden field for Dotpay Helper Form
     * @param string $name
     * @param string $value
     * @return array
     */
    protected function getHiddenField($name, $value)
    {
        return array(
            'type' => 'hidden',
            'name' => $name,
            'value' => $value
        );
    }
    
    /**
     * Adds field with Bylaw agreement of Dotpay to form
     * @return array
     */
    protected function addBylawField()
    {
        $byLawAgreements = $this->getByLaw();
        return array(
            'type' => 'checkbox',
            'name' => 'bylaw',
            'value' => '1',
            'checked' => true,
            'label' => $byLawAgreements,
            'required' => true
        );
    }
    
    /**
     * Adds field with Personal data agreement of Dotpay to form
     * @return array
     */
    protected function addPersonalDataField()
    {
        $personalDataAgreements = $this->getPersonalData();
        return array(
            'type' => 'input',
            'style' => 'display: none',
            'name' => 'personal_data',
            'value' => '1',
            'checked' => true,
            'label' => $personalDataAgreements,
            'required' => true
        );
    }

        /**
     * Returns submit field for Dotpay Helper Form
     * @return array
     */
    protected function getSubmitField()
    {
        return array(
            'type' => 'submit',
            'value' => '<span>'.$this->parent->module->l('Continue payment').'<i class="icon-chevron-right right"></i></span>',
            'class' => 'button btn btn-default standard-checkout button-medium',
            'label' => '</p>',
            'llabel' => '<p class="cart_navigation clearfix">'
        );
    }
    
    /**
     * Adds a channel to list of separated channels
     * @param int $id Id of channel
     */
    protected function addSeparatedChannel($id)
    {
        if (array_search($id, $this->enabledSeparatedChannels) === false) {
            $this->enabledSeparatedChannels[] = $id;
        }
    }
}
