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

if (!defined('_PS_VERSION_')) {
    exit;
}

define('DOTPAY_MODULE_NAME', 'dotpay');
define('DOTPAY_PLUGIN_DIR', _PS_ROOT_DIR_.'/modules/'.DOTPAY_MODULE_NAME);

require_once(DOTPAY_PLUGIN_DIR.'/DotpayFormHelper.php');
require_once(DOTPAY_PLUGIN_DIR.'/models/Config.php');
require_once(DOTPAY_PLUGIN_DIR.'/api/dev.php');
require_once(DOTPAY_PLUGIN_DIR.'/api/legacy.php');
require_once(DOTPAY_PLUGIN_DIR.'/controllers/front/payment.php');
require_once(DOTPAY_PLUGIN_DIR.'/models/Instruction.php');
require_once(DOTPAY_PLUGIN_DIR.'/models/CreditCard.php');
require_once(DOTPAY_PLUGIN_DIR.'/models/CardBrand.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/SellerApi.php');
require_once(DOTPAY_PLUGIN_DIR.'/classes/GithubApi.php');

/**
 * Function adds Dotpay Form Helper to Smarty tags
 * @param array $params
 * @param type $smarty
 * @return string
 */
function dotpayGenerateForm(array $params, $smarty)
{
    $data = (isset($params['form']))?$params['form']:[];
    return DotpayFormHelper::generate($data);
}

/**
 * Dotpay payment module
 */
class dotpay extends PaymentModule
{

    /**
     *
     * @var DotpayConfig
     */
    private $config;

    /**
     *
     * @var DotpayApi
     */
    private $api;

    /**
     * Initialize module
     */
    public function __construct()
    {
        $this->name = 'dotpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.4.3';
        $this->author = 'tech@dotpay.pl';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.9');
        $this->bootstrap = true;
        $this->controllers = array('payment', 'preparing', 'callback', 'back', 'status', 'confirm', 'ocmanage', 'ocremove');
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->module_key = '4a4585752c0aceb57586b3f669cf9421';
        parent::__construct();

        $this->displayName = $this->l('Dotpay');
        if (_PS_VERSION_ < 1.6) {
            $this->description = $this->l('WARNING! This Dotpay payment module is designed only for the PrestaShop 1.6 and later. For older version PrestaShop use an older version of the Dotpay payment module  available to download from the following address: https://github.com/dotpay/PrestaShop-1.6/tags');
            parent::uninstall();
        } else {
            $this->description = $this->l('Fast and secure internet payments');
        }

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Dotpay payment module?');

        $this->config = new DotpayConfig();

        if ($this->config->getDotpayApiVersion()=='legacy') {
            $this->api = new DotpayLegacyApi();
        } else {
            $this->api = new DotpayDevApi();
        }
    }

    /**
     * Return relative module path
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Installing module
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->update()) {
            return false;
        }
        return true;
    }

    /**
     * Updating module
     * @return bool
     */
    public function update()
    {
        if (!$this->setDefaultConfig() ||
            !$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('backOfficeHeader') ||
            !$this->registerHook('displayPaymentEU') ||
            !$this->registerHook('displayOrderDetail') ||
            !$this->registerHook('displayCustomerAccount') ||
            !$this->registerHook('displayShoppingCart') ||
            !$this->registerHook('displayAdminOrder') ||
            !$this->registerHook('displayAdminOrder') ||
            !$this->addDotpayNewStatus() ||
            !$this->setDefaultCarrierOptions() ||
            !$this->addDotpayWaitingRefundStatus() ||
            !$this->addDotpayFailedRefundStatus() ||
            !$this->addDotpayTotalRefundStatus() ||
            !$this->addDotpayPartialRefundStatus() ||
            !$this->addReturnTab() ||
            !DotpayInstruction::create() ||
            !DotpayCardBrand::create() ||
            !DotpayCreditCard::create()) {
                return false;
        }
        return true;
    }

    /**
     * Uninstalling module
     * @return bool
     */
    public function uninstall()
    {
        return DotpayCreditCard::drop() && $this->removeDotpayFee() && parent::uninstall();
    }

    /**
     * Removes Dotpay Fee product if it exists in shop's database
     * @return boolean
     */
    public function removeDotpayFee()
    {
        if (Validate::isInt($this->config->getDotpayExchVPid()) &&
           (Validate::isLoadedObject($product = new Product($this->config->getDotpayExchVPid()))) &&
           Validate::isInt($product->id)
        ) {
            $product->delete();
        }
        return true;
    }

    /**
     * Returns HTML for module settings
     * @return string
     */
    public function getContent()
    {
        $this->saveConfiguration();
        $sellerApi = new DotpaySellerApi($this->config->getDotpaySellerApiUrl());

        $version = DotpayGithubApi::getLatestVersion();
        $shipment_options = $this->getCarriers();

        $arraysettings1 = array(
                                'regMessEn' => $this->config->isDotpayTestMode() || !$this->config->isAccountConfigOk(),
                                'targetForUrlc' => $this->context->link->getModuleLink('dotpay', 'callback', array('ajax' => '1'), $this->isSSLEnabled()),
                                'moduleMainDir' => $this->_path,
                                'testMode' => $this->config->isDotpayTestMode(),
                                'oldVersion' => !version_compare(_PS_VERSION_, "1.6.0.1", ">="),
                                'badPhpVersion' => !version_compare(PHP_VERSION, "5.4", ">="),
                                'confOK' => $this->config->isAccountConfigOk() && $this->config->isDotpayEnabled(),
                                'moduleVersion' => $this->version,
                                'apiVersion' => $this->config->getDotpayApiVersion(),
                                'phpVersion' => PHP_VERSION,
                                'minorPhpVersion' => '5.4',
                                'badNewIdMessage' => $this->l('Incorrect ID (required 6 digits)'),
                                'badOldIdMessage' => $this->l('Incorrect ID (5 digits maximum)'),
                                'carriernotselectedMessage' => $this->l('You must choose a delivery method for all of this values'),
                                'badNewPinMessage' => $this->l('Incorrect PIN (minimum 16 and maximum 32 alphanumeric characters)'),
                                'badOldPinMessage' => $this->l('Incorrect PIN (0 or 16 alphanumeric characters)'),
                                'valueLowerThanZero' => $this->l('The value must be greater than zero.'),
                                'testSellerId' => $this->api->checkSellerId($this->config->getDotpayId()),
                                'testApiAccount' => $sellerApi->isAccountRight(
                                                        $this->config->getDotpayApiUsername(),
                                                        $this->config->getDotpayApiPassword(),
                                                        $this->config->getDotpayApiVersion()
                                ),
                                'testSellerPin' => $sellerApi->isSellerPinOk(
                                                        $this->config->getDotpayApiUsername(),
                                                        $this->config->getDotpayApiPassword(),
                                                        $this->config->getDotpayApiVersion(),
                                                        $this->config->getDotpayId(),
                                                        $this->config->getDotpayPIN()
                                ),
                                'urlWithNewVersion' => $version['url'],
                                'obsoletePlugin' => version_compare($version['version'], $this->version, '>'),
                                'canNotCheckPlugin' => $version['version'] === null,
                                'deliverylistmethod' =>$this->getConfigCarr()

                );


                for ($x = 0; $x <= count($shipment_options); $x++) {
                    $arraysettings1[$this->config->Carrrieridprefix().$x] = (int)(Tools::getValue($this->config->Carrrieridprefix().$x));
                }


        $this->context->smarty->assign($arraysettings1);


        $template = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $template.$this->renderForm();
    }

    /**
     * Hook for header section in admin area
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/back.css');
    }

    /**
     * Hook for header section in front area
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/front.css');
    }

    /**
     * Hook for payment gateways list in checkout site
     * @return string
     */
    public function hookPayment()
    {
        if (!$this->config->isDotpayEnabled()) {
            return '';
        }

        if ($this->active && $this->config->isAccountConfigOk()) {
            $this->smarty->assign($this->getSmartyVars());
            return $this->display(__FILE__, 'payment.tpl');
        }
    }

    /**
     * Hook for display shopping cart by clients
     * @param array $params Details of displayed shopping card
     * @return type
     */
    public function hookDisplayShoppingCart($params)
    {
        if (!$this->config->getDotpayExCh() || $this->config->getDotpayExchVPid()===null) {
            return '';
        }
        foreach ($params['products'] as $product) {
            if ($product["id_product"] == $this->config->getDotpayExchVPid()) {
                $this->context->cart->deleteProduct($product["id_product"]);
                Tools::redirect($this->getUrl());
                die();
            }
        }
        return '';
    }

    /**
     * Hook for payment gateways list in checkout site
     * @return string
     */
    public function hookDisplayCustomerAccount()
    {
        $this->smarty->assign(array(
            'actionUrl' => $this->context->link->getModuleLink('dotpay', 'ocmanage'),
        ));
        return $this->display(__FILE__, 'ocbutton.tpl');
    }

    /**
     * Hook for Advanced EU Compliance plugin
     * @param array $params
     * @return string
     */
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return '';
        }

        if (!$this->checkCurrency($params['cart'])) {
            return '';
        }

        $payment_options = array(
                'cta_text' => $this->l('Fast and secure internet payments'),
                'logo' => $this->_path.'views/img/dotpay_logo85.png',
                'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true)
        );

        return $payment_options;
    }

    /**
     * Returns a rendered template with Dotpay area on order details
     * @param array $params Details of current order
     * @return string
     */
    public function hookDisplayOrderDetail($params)
    {
        if (!$this->config->isDotpayRenewEn()) {
            return '';
        }
        $order = new Order(Tools::getValue('id_order'));
        $instruction = DotpayInstruction::getByOrderId($order->id);
        $context =  Context::getContext();
        if ($order->module=='dotpay') {
            if ($instruction != null && $this->ifRenewActiveForOrder($order)) {
                $context->cookie->dotpay_channel = $instruction->channel;
                $this->smarty->assign(array(
                    'isInstruction' => ($instruction->id!=null),
                    'instructionUrl' => $this->context->link->getModuleLink('dotpay', 'confirm', array('order_id'=>$order->id)),
                ));
            }

            if ($this->ifRenewActiveForOrder($order)) {
				 if ($order->current_state == _PS_OS_ERROR_ || $order->current_state == $this->config->getDotpayNewStatusId())
				 {
					$isReneworder = true;
				 } else{
					 $isReneworder = false;
				 }
                $this->smarty->assign(array(
                    'isRenew' => $isReneworder,
                    'paymentUrl' => $this->context->link->getModuleLink('dotpay', 'payment', array('order_id'=>$order->id))
                ));
            }
            return $this->display(__FILE__, 'renew.tpl');
        }
    }

    /**
     * Checks, if a possibility for given order haven't expired yet
     * @param Order $order object of order
     * @return bool
     */
    public function ifRenewActiveForOrder(Order $order) {
        $now = new DateTime();
        $orderAddDate = new DateTime($order->date_add);
        $numberOfRenewDays = $this->config->getDotpayRenewDays();
        return (empty($numberOfRenewDays) || ($orderAddDate < $now && $now->diff($orderAddDate)->format("%a") < $numberOfRenewDays));
    }

    /**
     * Hook for displaying order by shop admin
     * @param array $params Details of displayed order
     * @return type
     */
    public function hookDisplayAdminOrder($params)
    {
        if (!$this->config->isDotpayRefundEn()) {
            return '';
        }
        if (Tools::getValue('dotpay_refund')!==false) {
            if (Tools::getValue('dotpay_refund')=='success') {
                $this->context->controller->confirmations[] = $this->l('Request of refund was sent');
            } else if (Tools::getValue('dotpay_refund')=='error') {
                $this->context->controller->errors[] = $this->l('An error occurred during request of refund').'<br /><i>'.$this->context->cookie->dotpay_error.'</i>';
            }
        }
        $order = new Order($params['id_order']);
        $payments = OrderPayment::getByOrderId($order->id);
        foreach ($payments as $key => $payment) {
            if ($payment->amount < 0) {
                unset($payments[$key]);
            }
        }
        $paidViaDotpay = false;
        foreach ($payments as $payment) {
            $currency = Currency::getCurrency($order->id_currency);
            if ($payment->payment_method === $this->displayName && $currency["iso_code"] === 'PLN') {
                $paidViaDotpay = true;
            }
            break;
        }
        if ($paidViaDotpay) {
            $this->smarty->assign(array(
                'orderId' => $order->id,
                'payments' => $payments,
                'returnUrl' => $this->context->link->getAdminLink('AdminDotpayRefund')
            ));
            return $this->display(__FILE__, 'orderDetails.tpl');
        }
        return '';
    }

    /**
     * Register Dotpay Form Helper in Smarty engine
     */
    public function registerFormHelper()
    {
        $this->context->smarty->unregisterPlugin("function", "dotpayGenerateForm");
        $this->context->smarty->registerPlugin("function", "dotpayGenerateForm", "dotpayGenerateForm");
    }

    /**
     * Check if currency in cart is correct
     * @param \Cart $cart
     * @return boolean
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns HTML code for module settings form
     * @return string
     */
    private function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_F||M_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'saveDotpayConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                .'&configure='.$this->name
                                .'&tab_module='.$this->tab
                                .'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        $carriers_form = $this->getCarriersForm();
        $general_settings_form = $this->getConfigForm();

        if(is_array($carriers_form))
        {
            return $helper->generateForm(array($general_settings_form, $carriers_form));
        }else{
            return $helper->generateForm(array($general_settings_form));
        }

    }

    /**
     * Returns array data for Prestashop Form Helper
     * @return array
     */
    private function getConfigForm()
    {
        $optionsApi = array(
            array(
              'id_option2' => 'dev',
              'name2' => $this->l('dev (ID has 6 digits)')
            ),
            array(
              'id_option2' => 'legacy',
              'name2' => $this->l('legacy (ID has max 5 digits)')
            )
        );
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-wrench',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable this payment module'),
                        'name' => $this->config->getDotpayEnabledFN(),
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l('You can hide Dotpay gateway without uninstalling'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'class' => 'fixed-width-xxl',
                        'label' => $this->l('Select used Dotpay API version'),
                        'name' => $this->config->getDotpayApiVersionFN(),
                        'desc' => '<b>'.$this->l('dev is recommended').'</b><br /><span id="message-for-old-version">'.$this->l('Please contact the Customer Care to improve your API version to dev').':&nbsp;<a href="'.$this->l('https://ssl.dotpay.pl/en/customer_care').'" target="_blank"><b>'.$this->l('Contact').'</b></a></span>',
                        'required' => true,
                        'class' => 'api-select',
                        'options' => array(
                            'query' => $optionsApi,
                            'id' => 'id_option2',
                            'name' => 'name2'
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayIdFN(),
                        'label' => $this->l('ID'),
                        'prefix' => '<i style="font-weight: bold; color: #10279b; font-size: 1.4em;">&#35;</i>',
                        'size' => 6,
                        'maxlength' => 6,
                        'class' => 'fixed-width-sm',
                        'desc' => $this->l('Copy from Dotpay user panel').' <div id="infoID" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPINFN(),
                        'label' => $this->l('PIN'),
                        'prefix' => '<i class="icon-key" style="color: #10279b;"></i>',
                        'suffix' => '<i class="icon-eye-slash" id="eyelook" style="color: #2eacce; cursor : zoom-in;"></i>',
                        'class' => 'fixed-width-xxl',
                        'maxlength' => 32,
                        'desc' => $this->l('Copy only number (without "#" char) from the Dotpay user panel.').' <div id="infoPIN" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Test mode').'</span>',
                        'name' => $this->config->getDotpayTestModeFN(),
                        'is_bool' => true,
                        'class' => 'dev-option',
                        'desc' => $this->l('I\'m using Dotpay test account (test ID)').'<br><b>'.$this->l('Required Dotpay test account:').' <a href="https://ssl.dotpay.pl/test_seller/test/registration/?affilate_id=prestashop_module" target="_blank" title="'.$this->l('Dotpay test account registration').'">'.$this->l('registration').'</b></a>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Dotpay widget enabled').'</span>',
                        'name' => $this->config->getDotpayWidgetModeFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable Dotpay widget on shop site').'<br><b>'.$this->l('Disable this feature if you are using modules modifying checkout page').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),

                    array(
                        'type' => 'radio',
                        'label' => '<span class="">'.$this->l('Use the additional features necessary for postponed payments').'</span>',
                        'name' => $this->config->getDotpayPostponedPaymentFN(),
                        'is_bool' => true,
                        'class' => 'dev-option postponed-enable-option',
                        'hint' => $this->l('Enable if you want to use channels offering postponed payments.'),
                        'desc' => '<b>'.$this->l('The function is necessary if you have an active additional payment channel for postponed payments on your Dotpay account.').'</b><br>'.$this->l('Additional payment information such as: delivery address, date of account activation in the store or the number of previous orders can be sent to the payment operator.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),

                    array(
                        'type' => 'radio',
                        'label' => '<i class="icon-AdminTools" style="color: #10279b;"></i> <span class="dev-option advanced-mode-switch dotpayadvsett">'.$this->l('Advanced Mode').'</span>',
                        'name' => $this->config->getDotpayAdvancedModeFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Show advanced plugin settings'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Renew payment enabled'),
                        'is_bool' => true,
                        'class' => 'dev-option renew-enable-option',
						'desc' => $this->l('Logged in clients can resume interrupted payments').'<br><b>'.$this->l('Warning! Renewed order amount will be the same as during first payment attempt').'<br>'.$this->l('(changes in product prices will not be taken into account)').'</b>',
						'name' => $this->config->getDotpayRenewFN(),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayRenewDaysFN(),
						'label' => '<span class="dev-option renew-option">'.$this->l('Number of days to renew payments').'</span>',
                        'size' => 6,
                        'class' => 'fixed-width-sm',
						'desc' => $this->l('Enter for how many days customers will be able to renew their payments').'<br><b>'.$this->l('Leave blank if payment renew should not be restricted by time').'</b>',
                    ),
                    array(
                        'type' => 'text',
                        'label' => '<span class="dev-option lastInSection">'.$this->l('Currencies for which main channel is disabled').'</span>',
                        'name' => $this->config->getDotpayWidgetDisCurrFN(),
                        'prefix' => '<i class="icon-money" style="color: #407786;"></i>',
                        'class' => 'fixed-width-xxl',
                        'desc' => $this->l('Enter currency codes separated by commas, for example: EUR,USD,GBP').'<br><b>'.$this->l('Leave this field blank to display for all currencies').'</b>',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Credit card channel enabled').'</span>',
                        'name' => $this->config->getDotpayCreditCardFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable payment cards as separate channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('MasterPass channel enabled').'</span>',
                        'name' => $this->config->getDotpayMasterPassFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable MasterPass as separate channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),

                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('PayPo channel enabled').'</span>',
                        'name' => $this->config->getDotpayPayPoFN(),
                        'is_bool' => true,
                        'desc' => '<strong>'.$this->l('Enable PayPo as separate channel').'</strong><br>'.$this->l('Additional contract required before including this channel'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),

                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option lastInSection">'.$this->l('Blik channel enabled').'</span>',
                        'name' => $this->config->getDotpayBlikFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable Blik as separate channel').'<br><b>'.$this->l('Available only for PLN').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('OneClick channel enabled').'</span>',
                        'name' => $this->config->getDotpayOneClickFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable payments with one click for credit card channel (248)').'<br><b>'.$this->l('Contact Dotpay customer service before using this option').' <a href="http://www.dotpay.pl/kontakt/biuro-obslugi-klienta/" target="_blank" title="'.$this->l('Dotpay customer service').'">'.$this->l('Contact').'</a><br>'.$this->l('Requires Dotpay API username and password (enter below).').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Refund payment enabled')."</span>",
                        'name' => $this->config->getDotpayRefundFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Enable sending payments refund requests directly from your shop').'<br><b>'.$this->l('Contact Dotpay customer service before using this option').' <a href="http://www.dotpay.pl/kontakt/biuro-obslugi-klienta/" target="_blank" title="'.$this->l('Dotpay customer service').'">'.$this->l('Contact').'</a><br>'.$this->l('Requires Dotpay API username and password (enter below).').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => '<span class="dev-option">'.$this->l('Payment instructions on shop site')."</span>",
                        'name' => $this->config->getDotpayDispInstructionFN(),
                        'is_bool' => true,
                        'desc' => $this->l('Display transfer payment instructions without redirecting to Dotpay site').'<br><b>'.$this->l('Contact Dotpay customer service before using this option').' <a href="http://www.dotpay.pl/kontakt/biuro-obslugi-klienta/" target="_blank" title="'.$this->l('Dotpay customer service').'">'.$this->l('Contact').'</a><br>'.$this->l('Requires Dotpay API username and password (enter below).').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'API_DATA_DESC',
                        'label' => $this->l('API DATA (optional)'),
                        'class' => 'hidden dev-option',
                        'desc' => '<span style="font-style: normal; color: #555;">'.$this->l('Required for One Click, Payment Refund and displaying instructions for Transfer channels on shops website').'<br><b>'.$this->l('If none of those functions are used, leave fields below blank').'</b></span>',
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayApiUsernameFN(),
                        'prefix' => '<i class="icon-male" style="color: #9b6610;"></i>',
                        'label' => $this->l('Dotpay API username'),
                        'class' => 'fixed-width-xxl dev-option',
                        'desc' => $this->l('Your username for Dotpay user panel')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayApiPasswordFN(),
                        'prefix' => '<i class="icon-key" style="color: #9b6610;"></i>',
                        'label' => $this->l('Dotpay API password'),
                        'class' => 'fixed-width-xxl dev-option password-field lastInSection',
                        'desc' => $this->l('Your password for Dotpay user panel'),
                    ),

                    array(
                        'type' => 'radio',
                        'label' => $this->l('I have separate ID for foreign currencies'),
                        'name' => $this->config->getDotpayPVFN(),
                        'is_bool' => true,
                        'class' => 'dev-option pv-enable-option',
                        'desc' => $this->l('Enable separate payment channel for foreign currencies'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvIdFN(),
                        'label' => $this->l('ID for foreign currencies account'),
                        'prefix' => '<i style="font-weight: bold; color: #407786; font-size: 1.4em;">&#35;</i>',
                        'size' => 6,
                        'maxlength' => 6,
                        'class' => 'fixed-width-sm dev-option pv-option',
                        'desc' => $this->l('Copy only number (without "#" char) from the Dotpay user panel.').' <div id="infoID" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvPINFN(),
                        'label' => $this->l('PIN for foreign currencies account'),
                        'prefix' => '<i class="icon-key" style="color: #407786;"></i>',
                        'class' => 'fixed-width-xxl dev-option pv-option',
                        'maxlength' => 32,
                        'desc' => $this->l('Copy from Dotpay user panel').' <div id="infoPIN" /></div>',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayPvCurrenciesFN(),
                        'label' => $this->l('Currencies used by foreign currencies account'),
                        'prefix' => '<i class="icon-money" style="color: #407786;"></i>',
                        'class' => 'fixed-width-xxl dev-option pv-option lastInSection',
                        'desc' => $this->l('Enter currency codes separated by commas, for example: EUR,USD,GBP').'<br><b>'.$this->l('It is recommended to hide main channel for entered currencies').'</b>',
                    ),

                    array(
                        'type' => 'radio',
                        'label' => $this->l('Extracharge options'),
                        'name' => $this->config->getDotpayExChFN(),
                        'is_bool' => true,
                        'class' => 'dev-option excharge-enable-option',
                        'desc' => $this->l('Enable extra fee for Dotpay payment method').'<br><b>'.$this->l('Enabling this option will add required "Online payment - DOTPAYFEE" to your products').'</b>',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayExAmountFN(),
                        'label' => $this->l('Increase amount of order'),
                        'class' => 'fixed-width-lg dev-option exch-option',
                        'desc' => $this->l('Value of additional fee for given currency (eg. 5.23)')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayExPercentageFN(),
                        'label' => $this->l('Increase amount of order (in %)'),
                        'class' => 'fixed-width-lg dev-option exch-option lastInSection',
                        'desc' => $this->l('Value of additional fee for given currency in % (eg. 1.90)').'<br><b>'.$this->l('Bigger amount will be chosen').'</b>',
                    ),

                    array(
                        'type' => 'radio',
                        'label' => $this->l('Discount options'),
                        'name' => $this->config->getDotpayDiscountFN(),
                        'is_bool' => true,
                        'class' => 'dev-option discount-enable-option',
                        'desc' => $this->l('Enable discount for Dotpay payment method'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enable')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disable')
                            )
                        )
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayDiscAmountFN(),
                        'label' => $this->l('Reduce amount of order'),
                        'class' => 'fixed-width-lg dev-option discount-option',
                        'desc' => $this->l('Value of discount amount (in current price)')
                    ),
                    array(
                        'type' => 'text',
                        'name' => $this->config->getDotpayDiscPercentageFN(),
                        'label' => $this->l('Reduce amount of order (in %)'),
                        'class' => 'fixed-width-lg dev-option discount-option',
                        'desc' => $this->l('Value of discount for given currency in % (eg. 1.90)').'<br><b>'.$this->l('Bigger amount will be chosen').'</b>',
                    ),

                ),
                'submit' => array(
                    'class' => 'btn btn-success center-block',
                    'title' => $this->l('Save'),
                ),
            )
        );
    }




    public function getConfigCarr(){
        $shipment_options = $this->getCarriers();
         $values1 = null;

        if(is_array($shipment_options)){
            foreach ($shipment_options as $customization) {
              $datCarr = $this->config->Carrrieridprefix().$customization['id_option'];
              $values1[$datCarr] = Configuration::get($datCarr);
          }
      }



         return $values1;
    }

/**
 * return name of the assigned group to the carrier in the module settings
 *
 * @param [type] $a
 * @return string
 */
    public function getchosenCarries($a = null)
    {
        $data = $this->getConfigCarr();
        $value = '';
        if ($data != null)
        {
            foreach( $data as $key => $value ){

                $key= str_replace('CarrierDotpay_','',$key);
                if($key == (int)$a ){
                    return $value;
                }
            }
        }

        return $value;
    }



    /**
     * Returns settings values
     * @return array
     */
    private function getConfigFormValues()
    {

        $shipment_options = $this->getCarriers();

         $values1 = $this->getConfigCarr();


          $values2 = array(
            $this->config->getDotpayEnabledFN() => $this->config->isDotpayEnabled(),
            $this->config->getDotpayApiVersionFN() => $this->config->getDotpayApiVersion(),
            $this->config->getDotpayIdFN() => $this->config->getDotpayId(),
            $this->config->getDotpayPINFN() => $this->config->getDotpayPIN(),
            $this->config->getDotpayRenewFN() => $this->config->isDotpayRenewEn(),
            $this->config->getDotpayRenewDaysFN() => $this->config->getDotpayRenewDays(),
            $this->config->getDotpayRefundFN() => $this->config->isDotpayRefundEn(),
            $this->config->getDotpayDispInstructionFN() => $this->config->isDotpayDispInstruction(),
            $this->config->getDotpayMasterPassFN() => $this->config->isDotpayMasterPass(),
            $this->config->getDotpayBlikFN() => $this->config->isDotpayBlik(),
            $this->config->getDotpayOneClickFN() => $this->config->isDotpayOneClick(),
            $this->config->getDotpayCreditCardFN() => $this->config->isDotpayCreditCard(),
            $this->config->getDotpayWidgetModeFN() => $this->config->isDotpayWidgetMode(),
            $this->config->getDotpayChannelNameVisiblityFN() => $this->config->isDotpayWidgetChannelsName(),
            $this->config->getDotpayAdvancedModeFN() => $this->config->isDotpayAdvancedMode(),
            $this->config->getDotpayPostponedPaymentFN() => $this->config->isDotpayPostponedPayment(),
            $this->config->getDotpayPayPoFN() => $this->config->isDotpayPaypo(),
            $this->config->getDotpayWidgetDisCurrFN() => $this->config->getDotpayWidgetDisCurr(),
            $this->config->getDotpayTestModeFN() => $this->config->isDotpayTestMode(),
            $this->config->getDotpayPVFN() => $this->config->isDotpayPV(),
            $this->config->getDotpayPvIdFN() => $this->config->getDotpayPvId(),
            $this->config->getDotpayPvPINFN() => $this->config->getDotpayPvPIN(),
            $this->config->getDotpayPvCurrenciesFN() => $this->config->getDotpayPvCurrencies(),
            $this->config->getDotpayExChFN() => $this->config->getDotpayExCh(),
            $this->config->getDotpayExPercentageFN() => $this->config->getDotpayExPercentage(),
            $this->config->getDotpayExAmountFN() => $this->config->getDotpayExAmount(),
            $this->config->getDotpayDiscountFN() => $this->config->getDotpayDiscount(),
            $this->config->getDotpayDiscAmountFN() => $this->config->getDotpayDiscAmount(),
            $this->config->getDotpayDiscPercentageFN() => $this->config->getDotpayDiscPercentage(),
            $this->config->getDotpayApiUsernameFN() => $this->config->getDotpayApiUsername(),
            $this->config->getDotpayApiPasswordFN() => $this->config->getDotpayApiPassword(),
            'API_DATA_DESC' => '',
        );

        if(is_array($values1))
        {
            return array_merge($values2,$values1);
        } else {

        }
        return $values2;

    }

    /**
     * Save configuration
     */
    private function saveConfiguration()
    {
        if (Tools::isSubmit('saveDotpayConfig')) {
            $discountFlagBefore = $this->config->getDotpayDiscount();
            $extrachargeFlagBefore = $this->config->getDotpayExCh();
            $values = $this->getConfigFormValues();
            $keysToCorrect = array(
                $this->config->getDotpayExAmountFN(),
                $this->config->getDotpayExPercentageFN(),
                $this->config->getDotpayDiscAmountFN(),
                $this->config->getDotpayDiscPercentageFN()
            );
            foreach (array_keys($values) as $key) {
                $value = trim(Tools::getValue($key));
                if (in_array($key, $keysToCorrect)) {
                    $value = $this->makeCorrectNumber($value);
                }
                Configuration::updateValue($key, $value);
            }
            $discountFlagAfter = $this->config->getDotpayDiscount();
            $extrachargeFlagAfter = $this->config->getDotpayExCh();

            if ($extrachargeFlagBefore == false && $extrachargeFlagAfter == true) {
                $this->checkDotpayVirtualProduct();
            }
            if ($discountFlagBefore == false && $discountFlagAfter == true) {
                $this->addDotpayDiscount();
            }
        }
    }

    /**
     * Delivery method
     * To make a payment with postoned payment channel, specific data is required
     *
     */
    private function setDefaultCarrierOptions()
    {
        $shipment_options = $this->getCarriers();

        for ($x = 0; $x <= count($shipment_options); $x++) {
            echo Configuration::updateValue($this->config->Carrrieridprefix().$x, '');
        }

        return true;
    }



    /**
     * Makes the number values correct
     * @param float $input
     * @return float
     */
    private function makeCorrectNumber($input)
    {
        return preg_replace('/[^0-9\.]/', "", str_replace(',', '.', trim($input)));
    }

    /**
     * Copies status image to Prestashop system dir
     * @param type $source
     * @param type $dest
     * @return bool
     */
    private function copyStatusImage($source, $dest)
    {
        $target = mydirname(DOTPAY_PLUGIN_DIR, 3);
        return copy(DOTPAY_PLUGIN_DIR.'/views/img/'.$source.'.gif', $target.'/img/os/'.$dest.'.gif');
    }

    /**
     * Adds Dotpay new payment status if not exist
     * @return bool
     */
    private function addDotpayNewStatus()
    {
        if (Validate::isInt($this->config->getDotpayNewStatusId()) &&
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayNewStatusId()))) &&
           Validate::isInt($order_state_new->id)
        ) {
            return true;
        }
        $stateId = $this->addDotpayOrderStatus('Oczekuje na potwierdzenie płatności z Dotpay', 'Awaiting for Dotpay Payment confirmation', '#00abf4');
        if ($stateId === false) {
            return false;
        }
        $this->config->setDotpayNewStatusId($stateId);
        $this->copyStatusImage('wait', $stateId);
        return true;
    }

    /**
     * Adds Dotpay total refund status if not exist
     * @return bool
     */
    private function addDotpayTotalRefundStatus()
    {
        if (Validate::isInt($this->config->getDotpayTotalRefundStatusId()) &&
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayTotalRefundStatusId()))) &&
           Validate::isInt($order_state_new->id)
        ) {
            return true;
        }
        $stateId = $this->addDotpayOrderStatus('Całkowity zwrot płatności', 'Total refund of payment', '#f8d700');
        if ($stateId === false) {
            return false;
        }
        $this->config->setDotpayTotalRefundStatusId($stateId);
        $this->copyStatusImage('refund', $stateId);
        return true;
    }

    /**
     * Adds Dotpay partial refund status if not exist
     * @return bool
     */
    private function addDotpayPartialRefundStatus()
    {
        if (Validate::isInt($this->config->getDotpayPartialRefundStatusId()) &&
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayPartialRefundStatusId()))) &&
           Validate::isInt($order_state_new->id)
        ) {
            return true;
        }
        $stateId = $this->addDotpayOrderStatus('Częściowy zwrot płatności', 'Partial refund of payment', '#f7ff59');
        if ($stateId === false) {
            return false;
        }
        $this->config->setDotpayPartialRefundStatusId($stateId);
        $this->copyStatusImage('refund', $stateId);
        return true;
    }

    /**
     * Adds Dotpay waiting for refund status if not exist
     * @return bool
     */
    private function addDotpayWaitingRefundStatus()
    {
        if (Validate::isInt($this->config->getDotpayWaitingRefundStatusId()) &&
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayWaitingRefundStatusId()))) &&
           Validate::isInt($order_state_new->id)
        ) {
            return true;
        }
        $stateId = $this->addDotpayOrderStatus('Zwrot oczekuje na potwierdzenie', 'Refund is waiting for confirmation', '#ffe5d1');
        if ($stateId === false) {
            return false;
        }
        $this->config->setDotpayWaitingRefundStatusId($stateId);
        $this->copyStatusImage('waitrefund', $stateId);
        return true;
    }

    /**
     * Adds Dotpay waiting for refund status if not exist
     * @return bool
     */
    private function addDotpayFailedRefundStatus()
    {
        if (Validate::isInt($this->config->getDotpayFailedRefundStatusId()) &&
           (Validate::isLoadedObject($order_state_new = new OrderState($this->config->getDotpayFailedRefundStatusId()))) &&
           Validate::isInt($order_state_new->id)
        ) {
            return true;
        }
        $stateId = $this->addDotpayOrderStatus('Zwrot został odrzucony', 'Refund has rejected', '#ff6059');
        if ($stateId === false) {
            return false;
        }
        $this->config->setDotpayFailedRefundStatusId($stateId);
        $this->copyStatusImage('failrefund', $stateId);
        return true;
    }

    /**
     * Adds new Dotpay order status to shop's database. If successfully, returns id of new status.
     * @param string $plName Name of status in Polish
     * @param string $engName Name os status in English
     * @param string $color $Color of status in hexadecimal form
     * @return int|boolean
     */
    private function addDotpayOrderStatus($plName, $engName, $color)
    {
        $newOrderState = new OrderState();
        $newOrderState->name = array();
        foreach (Language::getLanguages() as $language) {
            if (Tools::strtolower($language['iso_code']) == 'pl') {
                $newOrderState->name[$language['id_lang']] = $plName;
            } else {
                $newOrderState->name[$language['id_lang']] = $engName;
            }
        }
        $newOrderState->module_name = $this->name;
        $newOrderState->send_email = false;
        $newOrderState->invoice = false;
        $newOrderState->unremovable = false;
        $newOrderState->color = $color;
        if (!$newOrderState->add()) {
            return false;
        }
        return $newOrderState->id;
    }

    /**
     * Adds Dotpay virtual product for extracharge option
     * @return bool
     */
    public function checkDotpayVirtualProduct()
    {
        if (Validate::isInt($this->config->getDotpayExchVPid()) &&
           (Validate::isLoadedObject($product = new Product($this->config->getDotpayExchVPid()))) &&
           Validate::isInt($product->id)
        ) {
            if ($this->isVPIncomplete($product)) {
                $this->setVPFeatures($product);
                $product->save();
                StockAvailable::setQuantity($product->id,NULL,$product->quantity);
            }
            return true;
        }

        $product = new Product();
        $this->setVPFeatures($product);
        if (!$product->add()) {
            return false;
        }
        $product->addToCategories(array(1));
        StockAvailable::setQuantity($product->id, null, $product->quantity);
        $this->config->setDotpayExchVPid($product->id);

        return true;
    }

    /**
     * Checks if Dotpay virtual product is complete
     * @param Product $product Dotpay virtual product object
     * @return bool
     */
    private function isVPIncomplete($product) {
        return (
            empty($product->name) ||
            empty($product->link_rewrite) ||
            empty($product->visibility) ||
            empty($product->reference) ||
            empty($product->price) ||
            empty($product->is_virtual) ||
            empty($product->online_only) ||
            empty($product->redirect_type) ||
            empty($product->quantity) ||
            empty($product->id_tax_rules_group) ||
            empty($product->active) ||
            empty($product->meta_keywords) ||
            empty($product->id_category) ||
            empty($product->id_category_default)
        );
    }

    /**
     * Sets values of Dotpay virtual product
     * @param Product $product Dotpay virtual product object
     */
    private function setVPFeatures($product)
    {
        $product->name = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Online payment');
        $product->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') => 'online-payment');
        $product->visibility = 'none';
        $product->reference = 'DOTPAYFEE';
        $product->price = 0.0;
        $product->is_virtual = 1;
        $product->online_only = 1;
        $product->redirect_type = '404';
        $product->quantity = 9999999;
        $product->id_tax_rules_group = 0;
        $product->active = 1;
        $product->meta_keywords = 'payment';
        $product->id_category = 1;
        $product->id_category_default = 1;
    }

    /**
     * Adds a return tab
     * @return bool
     */
    private function addReturnTab()
    {
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminDotpayRefund';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Dotpay Refunds';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;
        return $tab->add();
    }

    /**
     * Added Dotpay discount for reducing shipping cost
     * @return bool
     */
    private function addDotpayDiscount()
    {
        if (!Validate::isInt($this->config->getDotpayDiscountId())) {
            $voucher = new Discount();
            $voucher->id_discount_type = Discount::AMOUNT;
            $voucher->name = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Discount for online shopping');
            $voucher->description = array((int)Configuration::get('PS_LANG_DEFAULT') => 'Online payment');
            $voucher->value = 0;
            $voucher->code = md5(date("d-m-Y H-i-s"));
            $voucher->quantity = 9999999;
            $voucher->quantity_per_user = 9999999;
            $voucher->cumulable = 1;
            $voucher->cumulable_reduction = 1;
            $voucher->active = 1;
            $voucher->cart_display = 1;
            $now = time();
            $voucher->date_from = date('Y-m-d H:i:s', $now);
            $voucher->date_to = date('Y-m-d H:i:s', $now + (3600 * 24 * 365.25)*50);
            if (!$voucher->add()) {
                return false;
            }
            $this->config->setDotpayDiscountId($voucher->id);
        }
        return true;
    }

    /**
     * Set default configuration during installation
     * @return bool
     */
    private function setDefaultConfig()
    {
        $this->config->setDotpayEnabled(false)
                     ->setDotpayApiVersion('dev')
                     ->setDotpayId('')
                     ->setDotpayPIN('')
                     ->setDotpayRenew(true)
                     ->setDotpayRenewDays('')
                     ->setDotpayRefund(false)
                     ->setDotpayDispInstruction(false)
                     ->setDotpayTestMode(false)
                     ->setDotpayPostponedPayment(false)
                     ->setDotpayAdvancedMode(false)
                     ->setDotpayBlik(false)
                     ->setDotpayMasterPass(false)
                     ->setDotpayPaypo(false)
                     ->setDotpayOneClick(false)
                     ->setDotpayCreditCard(false)
                     ->setDotpayPV(false)
                     ->setDotpayPvId('')
                     ->setDotpayPvPIN('')
                     ->setDotpayPvCurrencies('')
                     ->setDotpayWidgetMode(false)
                     ->setDotpayWidgetDisCurr('')
                     ->setDotpayExCh(false)
                     ->setDotpayExAmount(0)
                     ->setDotpayExPercentage(0)
                     ->setDotpayDiscount(false)
                     ->setDotpayDiscAmount(0)
                     ->setDotpayApiUsername('')
                     ->setDotpayApiPassword('')
                     ->setDotpayDiscPercentage(0);
        return true;
    }

    /**
     * Returns variables required by Smarty channel list template
     * @return array
     */
    private function getSmartyVars()
    {
        $_GET['module'] = $this->name;
        $pc = FrontController::getController('dotpaypaymentModuleFrontController');
        return $pc->getArrayForSmarty();
    }


 	/**
     * Returns correct SERVER NAME or HOSTNAME
     * @return string
     */

 public function getHostName() {
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
     * Checks, if SSL is enabled during current connection
     * @return boolean
     */
    public function isSSLEnabled()
    {
        if (isset($_SERVER['HTTPS'])) {
            if (Tools::strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1') {
                return true;
            }
        } elseif  (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')) {
            return true;
        }
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'){
            return true;
        }

        return false;
    }

    /**
     * Returns URL of current page
     * @return string
     */
    public function getUrl()
    {
        $url = 'http';
        if ($this->isSSLEnabled()) {
            $url .= "s";
        }
        $url .= "://".$this->getHostName().$_SERVER["REQUEST_URI"];
        return $url;
    }


    /**
     * Get all of active carriers
     */

    private function getCarriers()
    {
        $response = array();
        $available_shipment_carriers = Carrier::getCarriers((int)$this->context->language->id);
       /*
        $response[0] = array(
            'id_option' => 0,
            'name' => 'Brak przewoźnika'
        );
        */
        $i = 0;
        foreach ($available_shipment_carriers as $shipment_carrier) {
            if ((int)$shipment_carrier['active'] === 1) {
                $response[$i]['id_option'] = $shipment_carrier['id_carrier'];
                $response[$i]['name'] = $shipment_carrier['name'];
                $response[$i]['delay'] = $shipment_carrier['delay'];
                $response[$i]['is_free'] = $shipment_carrier['is_free'];
                $i++;
            }
        }

        return $response;
    }


    private function getCarrierinfo($id)
    {
        $response = array();
        $available_shipment_carriers = Carrier::getCarriers((int)$this->context->language->id);

        foreach ($available_shipment_carriers as $shipment_carrier) {
            if ((int)$shipment_carrier['id_carrier'] === (int)$id ) {

                $response['id'] = $shipment_carrier['id_carrier'];
                $response['name'] = $shipment_carrier['name'];
                $response['delay'] = $shipment_carrier['delay'];
                $response['is_free'] = $shipment_carrier['is_free'];

            }
        }

        return $response;
    }




/**
 *  define groups of carrier (delivery method)
 */

function GetGroupsCarriers()  {


    $delivery_type =
    array(
        0 => array('name' => $this->l('-- No method, choose:'), 'param_value' => $this->config->getCarrierNoneFN()),
        1 => array('name' => $this->l('Pickup point like UPS Access point, DHL Parcel Shop'), 'param_value' => $this->config->getCarrierPointDeliveryFN()),
        2 => array('name' => $this->l('Courier'), 'param_value' => $this->config->getCarrierCounterFN()),
        3 => array('name' => $this->l('Pickup in shop (click&collect)'), 'param_value' => $this->config->getCarrierShopAtPlaceFN()),
        4 => array('name' => $this->l('Paczka w ruchu'), 'param_value' => $this->config->getCarrierParcelRuchFN()),
        5 => array('name' => $this->l('Parcel locker'), 'param_value' => $this->config->getCarrierParcelLockFN())
    );

        return $delivery_type;

    }



  /**
   *  fragment of form to define carrier groups
   */

 function getDisplayListCarriers()
 {
     $displayList = array();

     $shipment_options = $this->getCarriers();
     $Groups = $this->GetGroupsCarriers();


     for ($i = 0; $i < count($shipment_options); $i++) {


        $idCarrier = $shipment_options[$i]['id_option'];
        $carrierInfo = $this->getCarrierinfo($idCarrier);

        if($carrierInfo['is_free'] == 1 ) $isfree = $this->l('Yes'); else $isfree = $this->l('No');

         $displayList[$i] =  array(

                        'type' => 'select',
                        'lang' => true,
                        'label' => '<strong>'.$shipment_options[$i]['name'].'</strong> ('.$idCarrier.')',
                        'name' => $this->config->Carrrieridprefix().$shipment_options[$i]['id_option'],
                        'class' => 'fixed-width-xxl dp_selectmenupostponed',
                        'hint' => $idCarrier.': '.$carrierInfo['name'].'<br> '.$this->l('Delay shipment').': '.$carrierInfo['delay'].'<br> '.$this->l('Is free').': '.$isfree,
                        'required' => true,
                        'options' => array(
                            'query' => $Groups,
                            'id' => 'param_value',
                            'name' => 'name'
                             )
                        );

      }

     return $displayList;
 }



  /**
   *  Display form to define carrier groups
   */

private function getCarriersForm()
{
    if(count($this->getCarriers()))
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => '<a href="index.php?controller=AdminCarriers" target="_blank">'.$this->l('Settings groups for delivery method').'</a><br><span id="DP_CarriersInfo"> '
                    . $this->l('If you use payment channels such as \"postponed payments\", select the appropriate delivery method for your carriers.').'</span><div id="DotcarrierError"></div>',
                    'icon' => 'icon-truck'
                ),
                'input' => array_slice(
                                        $this->getDisplayListCarriers()
                                    ,0),

                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-success center-block saveDotpayConfig1'
                    ),
                ),
        );
    }else{
        return null;
    }

}




}

/**
 * Fix for PHP older than 7.0
 * @param string $dir
 * @param int $levels
 * @return string
 */
function mydirname($dir, $levels)
{
    while (--$levels) {
        $dir = dirname($dir);
    }
    return $dir;
}
