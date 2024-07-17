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

require_once(DOTPAY_PLUGIN_DIR.'/api/api.php');

class DotpayNextApi extends DotpayApi
{
    /**
     * Status name of rejected operation
     */
    const OPERATION_REJECTED = 'rejected';

    /**
     * Status name of completed operation
     */
    const OPERATION_COMPLETED = 'completed';

    /**
     * Returns list of payment channels
     * @return array
     */
    public function getChannelList()
    {
        $oneclickAgreements = $this->parent->module->l('I agree to repeated loading bill my credit card for the payment One-Click by way of purchase of goods or services offered by the store.');
        $channelList = array();
        $targetUrl = $this->parent->getPreparingUrl();
        if ($this->config->isDotpayOneClick() && $this->getChannelData(self::$ocChannel) && $this->parent->isMainChannelEnabled()) {
            if (Context::getContext()->customer->isLogged()) {
                $this->addSeparatedChannel(self::$ocChannel);
                $creditCards = DotpayCreditCard::getAllCardsForCustomer(Context::getContext()->customer->id);
                $creditCardsValues = array();
                $img = '<img class="dotpay-card-logo" ';
                foreach ($creditCards as $creditCard) {
                    $creditCardsValues[$creditCard->mask.' ('.$creditCard->brand.')'] = $creditCard->id;
                    $img .= 'data-card-'.$creditCard->cc_id.'="'.$creditCard->card_brand->image.'" ';
                }
                $img .= '/>';
                $ocManageLink = Context::getContext()->link->getModuleLink($this->parent->module->name, 'ocmanage');

                $channelList['oneclick'] = array(
                    'form' => $this->getFormHeader('oneclick', $targetUrl),
                    'fields' => array(
                        $this->getHiddenField('order_id', Tools::getValue('order_id')),
                        array(
                            'type' => 'radio',
                            'name' => 'dotpay_type',
                            'id' => 'select_saved_card',
                            'value' => 'oneclick',
                            'class' => 'oneclick-margin',
                            'label' => $this->parent->module->l('Select a registered card').'&nbsp;(<a href="'.$ocManageLink.'" target="_blank">'.$this->parent->module->l('Manage your registered cards').'</a>)',
                            'required' => true
                        ),
                        array(
                            'type' => 'select',
                            'name' => 'credit_card',
                            'id' => 'saved_credit_cards',
                            'class' => 'oneclick-margin',
                            'values' => $creditCardsValues,
                            'label' => $img
                        ),
                        array(
                            'type' => 'radio',
                            'name' => 'dotpay_type',
                            'value' => 'oneclick_register',
                            'class' => 'oneclick-margin',
                            'label' => $this->parent->module->l('Register a new card'),
                            'required' => true
                        ),
                        array(
                            'type' => 'checkbox',
                            'name' => 'oneclick_agreements',
                            'value' => '1',
                            'checked' => true,
                            'label' => $oneclickAgreements,
                            'required' => true
                        ),
                        $this->addBylawField(),
                        $this->addPersonalDataField(),
                        $this->getSubmitField(),
                    ),
                    'image' => $this->parent->getDotOneClickLogo(),
                    'description' => "&nbsp;&nbsp;<strong>".$this->parent->module->l("Credit Card - One Click")."</strong>",
                );
            } else {
                $channelList['oneclick'] = array(
                    'form' => $this->getFormHeader('oneclick', $targetUrl),
                    'fields' => array(
                        array(
                            'style' => 'display: none',
                            'type' => 'input',
                            'name' => 'oneclick_fault',
                            'label' => '<p class="alert alert-warning" style="display: block;">'.$this->parent->module->l("You must be logged to use this channel").'</p>'
                        ),
                        array(
                            'type' => 'submit',
                            'disabled' => 'disabled',
                            'value' => '<span>'.$this->parent->module->l('Continue payment').'<i class="icon-chevron-right right"></i></span>',
                            'class' => 'button btn btn-default standard-checkout button-medium',
                            'label' => '</p>',
                            'llabel' => '<p class="cart_navigation clearfix">'
                        )
                    ),
                    'image' => $this->parent->getDotOneClickLogo(),
                    'description' => "&nbsp;&nbsp;<strong>".$this->parent->module->l("Credit Card - One Click"),
                );
            }
        }

        $ccChannel = null;
        $availableCardChannels = array(self::$ocChannel, self::$ccChannel);
        foreach ($availableCardChannels as $channel) {
            if ($this->getChannelData($channel)) {
                $ccChannel = $channel;
                break;
            }
        }
        if ($this->config->isDotpayCreditCard() && $ccChannel !== null && $this->parent->isMainChannelEnabled()) {
            $this->addSeparatedChannel($ccChannel);
            $channelList['cc'] = array(
                'form' => $this->getFormHeader('cc', $targetUrl),
                'fields' => array(
                    $this->getHiddenField('dotpay_type', 'cc'),
                    $this->getHiddenField('channel', $ccChannel),
                    $this->getHiddenField('order_id', Tools::getValue('order_id')),
                    $this->addBylawField(),
                    $this->addPersonalDataField(),
                    $this->getSubmitField(),
                ),
                'image' => $this->parent->getDotCreditCardLogo(),
                'description' => "&nbsp;&nbsp;".$this->parent->module->l("Pay with your credit card"),
            );
        }

        if ($this->parent->isDotpayPVEnabled() && $this->getChannelData(self::$pvChannel, true)) {
            $this->addSeparatedChannel(self::$pvChannel);
            $channelList['pv'] = array(
                'form' => $this->getFormHeader('pv', $targetUrl),
                'fields' => array(
                    $this->getHiddenField('dotpay_type', 'pv'),
                    $this->getHiddenField('order_id', (int)Tools::getValue('order_id')),
                    $this->getHiddenField('id', $this->config->getDotpayPvId()),
                    $this->addBylawField(),
                    $this->addPersonalDataField(),
                    $this->getSubmitField(),
                ),
                'image' => $this->parent->getDotPVLogo(),
                'description' => "&nbsp;&nbsp;".$this->parent->module->l("Pay with your credit card"),
            );
        }

        if ($this->config->isDotpayBlik() && $this->getChannelData(self::$blikChannel) && Tools::strtoupper($this->parent->getDotCurrency() == 'PLN') && $this->parent->isMainChannelEnabled()) {
            $this->addSeparatedChannel(self::$blikChannel);
            $blikText = $this->parent->module->l("BLIK code");
            $channelList['blik'] = array(
                'form' => $this->getFormHeader('blik', $targetUrl),
                'fields' => array(
                    $this->getHiddenField('dotpay_type', 'blik'),
                    $this->getHiddenField('order_id', Tools::getValue('order_id')),
                    array(
                        'type' => 'text',
                        'name' => 'blik_code',
                        'value' => '',
                        'placeholder' => $blikText,
                        'required' => true,
                        'maxlength' => 6,
                        'pattern' => '[0-9]{6}',
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, ""); this.value= this.value.replace(/(\..*)\./g, "$1");'
                    ),
                    $this->addBylawField(),
                    $this->addPersonalDataField(),
                    $this->getSubmitField(),
                ),
                'image' => $this->parent->getDotBlikLogo(),
                'description' => "&nbsp;&nbsp;<strong>".$this->parent->module->l("Blik"),
            );
        }

        if ($this->config->isDotpayMasterPass() && $this->getChannelData(self::$mpChannel) && $this->parent->isMainChannelEnabled()) {
            $this->addSeparatedChannel(self::$mpChannel);
            $channelList['mp'] = array(
                'form' => $this->getFormHeader('mp', $targetUrl),
                'fields' => array(
                    $this->getHiddenField('dotpay_type', 'mp'),
                    $this->getHiddenField('order_id', Tools::getValue('order_id')),
                    $this->addBylawField(),
                    $this->addPersonalDataField(),
                    $this->getSubmitField(),
                ),
                'image' => $this->parent->getDotMasterPassLogo(),
                'description' => "&nbsp;&nbsp;".$this->parent->module->l("MasterPass"),
            );
        }
//PayPo
        if ($this->config->isDotpayPayPo() && $this->getChannelData(self::$paypoChannel) &&  (float)$this->parent->getDotAmount() >= self::$paypoChannelamountmin && (float)$this->parent->getDotAmount() <= self::$paypoChannelamountmax && Tools::strtoupper($this->parent->getDotCurrency()) == 'PLN') {
       
            $this->addSeparatedChannel(self::$paypoChannel);
            $channelList['paypo'] = array(
                'form' => $this->getFormHeader('paypo', $targetUrl),
                'fields' => array(
                    $this->getHiddenField('dotpay_type', 'paypo'),
                    $this->getHiddenField('order_id', Tools::getValue('order_id')),
                    $this->addBylawField(),
                    $this->addPersonalDataField(),
                    $this->getSubmitField(),
                ),
                'image' => $this->parent->getDotPayPoLogo(),
                'description' => "&nbsp;&nbsp;".$this->parent->module->l("PayPo"),
            );
        }


        if ($this->parent->isMainChannelEnabled() && !$this->isMainChannelEmpty()) {
            $extendedWidget = '<div class="selected-channel-message">'.
                              $this->parent->module->l("Selected payment channel").
                              ':&nbsp;&nbsp; <a href="#" class="channel-selected-change">'.
                              $this->parent->module->l("change channel").
                              '&nbsp;&raquo;</a></div><div class="selectedChannelContainer channels-wrapper"><hr /></div>'.
                              '<div class="collapsibleWidgetTitle">'.$this->parent->module->l("Available channels").':</div>';
            $channelList['dotpay'] = array(
                'form' => $this->getFormHeader('dotpay', $targetUrl),
                'image' => $this->parent->getDotpayLogo(),
                'description' => "&nbsp;&nbsp;<strong>".$this->parent->module->l(" Przelewy24 ")."</strong>",
            );
            $fields = array(
                $this->getHiddenField('dotpay_type', 'dotpay'),
                $this->getHiddenField('order_id', Tools::getValue('order_id'))
            );
            if ($this->config->isDotpayWidgetMode()) {
                $fields[] = array(
                    'type' => 'hidden',
                    'name' => 'widget',
                    'value' => $this->config->isDotpayWidgetMode(),
                    'label' => $extendedWidget.'<p class="my-form-widget-container"></p>'
                );
                $fields[] = $this->addBylawField();
                $fields[] = $this->addPersonalDataField();
            }
            $fields[] = $this->getSubmitField();
            $channelList['dotpay']['fields'] = $fields;
        }
        return $channelList;
    }

    /**
     * Check confirm message from Dotpay
     * @return bool
     */
    public function checkConfirm()
    {
        if ($this->isSelectedPvChannel()) {
            $start = $this->config->getDotpayPvPIN().$this->config->getDotpayPvId();
        } else {
            $start = $this->config->getDotpayPIN().$this->config->getDotpayId();
        }
        $signature = $start.
        Tools::getValue('operation_number').
        Tools::getValue('operation_type').
        Tools::getValue('operation_status').
        Tools::getValue('operation_amount').
        Tools::getValue('operation_currency').
        Tools::getValue('operation_withdrawal_amount').
        Tools::getValue('operation_commission_amount').
        Tools::getValue('is_completed').
        Tools::getValue('operation_original_amount').
        Tools::getValue('operation_original_currency').
        Tools::getValue('operation_datetime').
        Tools::getValue('operation_related_number').
        Tools::getValue('control').
        Tools::getValue('description').
        Tools::getValue('email').
        Tools::getValue('p_info').
        Tools::getValue('p_email').
		Tools::getValue('credit_card_issuer_identification_number').
        Tools::getValue('credit_card_masked_number').
        Tools::getValue('credit_card_expiration_year').
        Tools::getValue('credit_card_expiration_month').
        Tools::getValue('credit_card_brand_codename').
        Tools::getValue('credit_card_brand_code').
        Tools::getValue('credit_card_unique_identifier').
        Tools::getValue('credit_card_id').
		Tools::getValue('channel').
        Tools::getValue('channel_country').
        Tools::getValue('geoip_country').
        Tools::getValue('payer_bank_account_name').
        Tools::getValue('payer_bank_account').
        Tools::getValue('payer_transfer_title').
        Tools::getValue('blik_voucher_pin').
        Tools::getValue('blik_voucher_amount').
        Tools::getValue('blik_voucher_amount_used');
        return (Tools::getValue('signature') === hash('sha256', $signature));
    }

    /**
     * Returns flag, if was selected PV channel
     */
    public function isSelectedPvChannel()
    {
        return ($this->parent->isDotSelectedCurrency($this->config->getDotpayPvCurrencies(), $this->getOperationCurrency()) &&
                Tools::getValue('channel')==self::$pvChannel &&
                $this->config->isDotpayPV() &&
                $this->config->getDotpayPvId()==Tools::getValue('id'));
    }

    /**
     * Returns total amount from confirm message
     * @return string|bool
     */
    public function getTotalAmount()
    {
        return Tools::getValue('operation_original_amount');
    }

    /**
     * Returns currency from confirm message
     * @return string|bool
     */
    public function getOperationCurrency()
    {
        return Tools::getValue('operation_original_currency');
    }

    /**
     * Returns name of operation status
     * @return string|bool
     */
    public function getOperationStatusName()
    {
        return Tools::getValue('operation_status');
    }

    /**
     * Returns operation number from confirm message
     * @return string|bool
     */
    public function getOperationNumber()
    {
        return Tools::getValue('operation_number');
    }

    /**
     * Returns related operation number from confirm message
     * @return string|bool
     */
    public function getRelatedOperationNumber()
    {
        return Tools::getValue('operation_related_number');
    }

    /**
     * Returns new order state identifier from confirm message
     * @param \OrderState $lastOrderState PrestaShop object with last order state
     * @return int
     */
    public function getNewOrderState($lastOrderState)
    {
        $actualState = null;
        switch ($this->getOperationStatusName()) {
            case "new":
                if($lastOrderState->id == _PS_OS_OUTOFSTOCK_UNPAID_) {
                    $actualState = _PS_OS_OUTOFSTOCK_UNPAID_;
                } else {
                    $actualState = $this->config->getDotpayNewStatusId();
                }
                break;
            case self::OPERATION_COMPLETED:
                if($lastOrderState->id == _PS_OS_OUTOFSTOCK_UNPAID_) {
                    $actualState = _PS_OS_OUTOFSTOCK_PAID_;
                } else {
                    $actualState = _PS_OS_PAYMENT_;
                }
                break;
            case self::OPERATION_REJECTED:
                $actualState = _PS_OS_ERROR_;
                break;
            case "processing_realization_waiting":
                $actualState = $this->config->getDotpayNewStatusId();
                break;
            case "processing_realization":
                $actualState = $this->config->getDotpayNewStatusId();
        }
        return $actualState;
    }


    /**
     * Returns hidden form for Dotpay Helper Form
     * @return array
     */
    public function getHiddenForm()
    {
        $type = Tools::getValue('dotpay_type');
        $formFields = array();
        switch($type) {
            case 'oneclick':
                $fields = $this->getHiddenFieldsOneClickCard();
                break;
            case 'oneclick_register':
                $fields = $this->getHiddenFieldsOneClickRegister();
                break;
            case 'pv':
                $fields = $this->getHiddenFieldsPV();
                break;
            case 'cc':
                $fields = $this->getHiddenFieldsCreditCard();
                break;
            case 'mp':
                $fields = $this->getHiddenFieldsMasterPass();
                break;
            case 'paypo':
                $fields = $this->getHiddenFieldsPayPo();
                break;    
            case 'blik':
                $fields = $this->getHiddenFieldsBlik();
                break;
            case 'dotpay':
            default:
                $fields = $this->getHiddenFieldsDotpay();
                break;
        }
        foreach ($fields as $name => $value) {
            $formFields[] = $this->getHiddenField($name, $value);
        }
        if ($type=='pv') {
            $id = $this->config->getDotpayPvId();
            $pin = $this->config->getDotpayPvPIN();
        } else {
            $id = $this->config->getDotpayId();
            $pin = $this->config->getDotpayPIN();
        }
        $formFields[] = $this->getHiddenField('chk', $this->generateCHK($id, $pin, $fields));

        return array(
            'form'=>  $this->getFormHeader($type),
            'fields' => $formFields
        );
    }

    /**
     * Performs actions on preparing form
     * @param string $action
     * @param array $params
     * @return boolean
     */
    public function onPrepareAction($action, $params)
    {
        switch($action) {
            case 'oneclick_register':
                return $this->onPrepareOneClick($params);
        }
    }

    /**
     * Check, if channel is in channels groups
     * @param int $channelId
     * @param array $group
     * @return boolean
     */
    public function isChannelInGroup($channelId, array $groups)
    {
        $result = $this->getApiChannels();

        if (isset($result['channels']) && is_array($result['channels'])) {
            foreach ($result['channels'] as $channel) {
                if (isset($channel['group']) && $channel['id']==$channelId && in_array($channel['group'], $groups)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns hidden fields for OneClick channel
     * @return array
     */
    protected function getHiddenFieldsOneClick()
    {
        $hiddenFields = $this->getHiddenFields();

        $hiddenFields['channel'] = (string)self::$ocChannel;
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for OneClick selected card
     * @return string
     */
    protected function getHiddenFieldsOneClickCard()
    {
        $hiddenFields = $this->getHiddenFieldsOneClick();
        $cc = new DotpayCreditCard(Tools::getValue('credit_card'));

        $hiddenFields['credit_card_customer_id'] = (string)$cc->hash;
        $hiddenFields['credit_card_id'] = (string)$cc->card_id;

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for OneClick register card
     * @return type
     */
    public function getHiddenFieldsOneClickRegister()
    {
        $hiddenFields = $this->getHiddenFieldsOneClick();
        $cc = DotpayCreditCard::getCreditCardByOrder($this->parent->getLastOrderNumber());
        $hash = ($cc !== null)?$cc->hash:null;

        $hiddenFields['credit_card_store'] = '1';
        $hiddenFields['credit_card_customer_id'] = (string)$hash;

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for PV channel
     * @return array
     */
    protected function getHiddenFieldsPV()
    {
        $hiddenFields = $this->getHiddenFields();

        $hiddenFields['channel'] = (string)self::$pvChannel;
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';
        $hiddenFields['id'] = (string)$this->config->getDotpayPvId();

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for Credit card channel
     * @return array
     */
    protected function getHiddenFieldsCreditCard()
    {
        $hiddenFields = $this->getHiddenFields();

        $hiddenFields['channel'] = (string)Tools::getValue('channel');
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for MasterPass channel
     * @return array
     */
    protected function getHiddenFieldsMasterPass()
    {
        $hiddenFields = $this->getHiddenFields();

        $hiddenFields['channel'] = (string)self::$mpChannel;
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';

        return $hiddenFields;
    }

        /**
     * Returns hidden fields for PayPo channel
     * @return array
     */
    protected function getHiddenFieldsPayPo()
    {
        $hiddenFields = $this->getHiddenFields();

        $hiddenFields['channel'] = (string)self::$paypoChannel;
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for standard Dotpay channel
     * @return array
     */
    protected function getHiddenFieldsDotpay()
    {
        $hiddenFields = $this->getHiddenFields();

        if ($this->config->isDotpayWidgetMode()) {
            $hiddenFields['channel'] = (string)Tools::getValue('channel');
            $hiddenFields['ch_lock'] = '0';
            $hiddenFields['type'] = '4';
            $hiddenFields['bylaw'] = '1';
            $hiddenFields['personal_data'] = '1';
        }

        return $hiddenFields;
    }

    /**
     * Returns hidden fields for Blik channel
     * @return array
     */
    protected function getHiddenFieldsBlik()
    {
        $hiddenFields = $this->getHiddenFields();

        //if (!$this->config->isDotpayTestMode()) {
            $hiddenFields['blik_code'] = (string) Tools::getValue('blik_code');
       // }
        $hiddenFields['channel'] = (string) self::$blikChannel;
        $hiddenFields['ch_lock'] = '0';
        $hiddenFields['type'] = '4';
        $hiddenFields['bylaw'] = '1';
        $hiddenFields['personal_data'] = '1';

        return $hiddenFields;
    }

    /**
     * Returns standard hidden fields
     * @return array
     */
    private function getHiddenFields()
    {
        $streetData = $this->parent->getDotStreetAndStreetN1();

        $dotPostForm = array(
                        'id' => (string)$this->parent->getDotId(),
                        'control' => (string)$this->parent->getDotControl(),
                        'p_info' => (string)$this->parent->getDotPinfo(),
                        'amount' => (string)$this->parent->getDotAmount(),
                        'currency' => (string)$this->parent->getDotCurrency(),
                        'description' => (string)$this->parent->getDotDescription(),
                        'lang' => (string)$this->parent->getDotLang(),
                        'url' => (string)$this->parent->getDotUrl(),
                        'urlc' => (string)$this->parent->getDotUrlC(),
                        'api_version' => (string)$this->config->getDotpayApiVersion(),
                        'type' => '4',
                        'ch_lock' => '0',
                        'firstname' => (string)$this->parent->getDotFirstname(),
                        'lastname' => (string)$this->parent->getDotLastname(),
                        'email' => (string)$this->parent->getDotEmail(),
                        'ignore_last_payment_channel' => '1',               
                        );


        if( null != trim($this->parent->getDotPhone()))
        {
            $dotPostForm["phone"] = (string) $this->parent->getDotPhone();
        }
        if( null != trim($streetData['street']))
        {
            $dotPostForm["street"] = (string) $streetData['street'];
        }
        if( null != trim($streetData['street_n1']))
        {
            $dotPostForm["street_n1"] = (string) $streetData['street_n1'];
        }
        if( null != trim($this->parent->getDotCity()))
        {
            $dotPostForm["city"] = (string) $this->parent->getDotCity();
        }
        if( null != trim($this->parent->getDotPostcode()))
        {
            $dotPostForm["postcode"] = (string) $this->parent->getDotPostcode();
        }
        if( null != trim($this->parent->getDotCountry()))
        {
            $dotPostForm["country"] = (string) $this->parent->getDotCountry();
        }
        if( null != trim($this->parent->PayerDatadostawaJsonBase64()) && $this->config->isDotpayPostponedPayment())
        {
            $dotPostForm["customer"] = (string) $this->parent->PayerDatadostawaJsonBase64();
        }


        return $dotPostForm;
        
    }


    /**
     * Executed when credit card is prepared in One Click method
     * @param array $params Needed params
     * @return boolean
     */
    protected function onPrepareOneClick($params)
    {
        $cc = new DotpayCreditCard();
        $cc->order_id = $params['order'];
        $cc->customer_id = $params['customer'];
        $cc->register_date = date('d-m-Y');
        return $cc->save();
    }

    /**
     * Checks if main channel will be empty
     * @return boolean
     */
    private function isMainChannelEmpty()
    {

			$channels1 = $this->getApiChannels();

		if(isset($channels1['channels']) && $channels1['channels'] != ''){
			$channels = $channels1['channels'];
			$separatedChannels = $this->getSeparatedChannelsList();
			foreach ($channels as $number => $channel) {
				if (($index = array_search($channel['id'], $separatedChannels)) !== false) {
					unset($channels[$number]);
					unset($separatedChannels[$index]);
				}
				if (!count($separatedChannels)) {
					break;
				}
			}
			return !count($channels);
		}else{
				return false;
		}
    }



    /**
     * Generate CHK for seller and payment data
     * @param type $DotpayPin Dotpay seller PIN
     * @param array $ParametersArray parameters of payment
     * @return string
     */
    
    
    ## function: counts the checksum from the defined array of all parameters

    protected function generateCHK($tmp=null, $DotpayPin, $ParametersArray)
    {
        
            //sorting the parameter list
            ksort($ParametersArray);
            
            // Display the semicolon separated list
            $paramList = implode(';', array_keys($ParametersArray));
            
            //adding the parameter 'paramList' with sorted list of parameters to the array
            $ParametersArray['paramsList'] = $paramList;
            
            //re-sorting the parameter list
            ksort($ParametersArray);
            
            //json encoding  
            $json = json_encode($ParametersArray, JSON_UNESCAPED_SLASHES);
    
        return hash_hmac('sha256', $json, $DotpayPin, false);
       
    }



    /**
     * Returns amount after extra charge
     * @return type
     */
    public function getExtrachargeAmount($inDefaultCurrency = false)
    {
        if (!$this->config->getDotpayExCh()) {
            return 0.0;
        }
        $amount = (float)$this->parent->getDotAmount();
        $exPercentage = $this->getFormatAmount($amount * $this->config->getDotpayExPercentage()/100.0);
        $exAmount = $this->getFormatAmount($this->config->getDotpayExAmount());
        $price = max($exPercentage, $exAmount);
        if ($inDefaultCurrency) {
            $price = $this->getFormatAmount(Tools::convertPrice($price, $this->parent->getDotCurrencyId(), false));
        }
        return $price;
    }

     /** Returns info about test mode
     * @return type
     */
    public function getinfoaboutTest()
    {
        if ($this->config->isDotpayTestMode()) {
            return true;
        }else{
			return false;
		}
    }
    /**
     * Returns amount after discount for Dotpay
     * @return type
     */
    public function getDiscountAmount()
    {
        if (!$this->config->getDotpayDiscount()) {
            return 0.0;
        }
        $amount = $this->parent->getDotShippingAmount();
        $discPercentage = $this->getFormatAmount($amount * $this->config->getDotpayDiscPercentage()/100.0);
        $discAmount = $this->config->getDotpayDiscAmount();
        $tmpPrice = max($discPercentage, $discAmount);
        return min($tmpPrice, $amount);
    }

    /**
     * Checks if seller account is right
     * @param type $sellerId
     * @return boolean
     */
    public function checkSellerId($sellerId)
    {
        if (empty($sellerId)) {
            return false;
        }
        $dotpayUrl = $this->config->getDotpayTargetUrl();
        $curlUrl = "{$dotpayUrl}payment_api/v1/channels/?id={$sellerId}";

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
        $result = Tools::jsonDecode($resultJson, true);
        return !(isset($result['error_code']) && $result['error_code'] == 'UNKNOWN_ACCOUNT');
    }

    /**
     * Returns operation type
     * @return string
     */
    public function getOperationType()
    {
        return Tools::getValue('operation_type');
    }
}
