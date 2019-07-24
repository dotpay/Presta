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

require_once(DOTPAY_PLUGIN_DIR.'/vendor/simple_html_dom.php');

/**
 * Model of payment instruction
 */
class DotpayInstruction extends ObjectModel
{
    /**
     *
     * @var inte Instruction id
     */
    public $instruction_id;
    
    /**
     *
     * @var int Id of order, which is connected with instruction
     */
    public $order_id;
   
    
    /**
     *
     * @var string Number of payment
     */
    public $number;

    /**
     *
     * @var string Description of payment
     */
    public $title;
    
    /**
     *
     * @var string Hash of payment, which is used to generating instruction url
     */
    public $hash;
    
    /**
     *
     * @var string|null Number of bank account, which is used for transfer or null when instruction applies cash payment
     */
    public $bank_account;
    
    /**
     *
     * @var boolean Flag if instruction applies cash
     */
    public $is_cash;
    
    /**
     *
     * @var float Amount of payment
     */
    public $amount;
    
    /**
     *
     * @var string Code of currency
     */
    public $currency;
    
    /**
     *
     * @var int Id of payment channel
     */
    public $channel;
    
    /**
     * Name of payment recipient
     */
    const DOTPAY_NAME = 'Dotpay sp. z o.o.';
    
    /**
     * Street of payment recipient
     */
    const DOTPAY_STREET = 'Wielicka 28B';
    
    /**
     * Post code and city of payment recipient
     */
    const DOTPAY_CITY = '30-552 Kraków';
    
    public static $definition = array(
        'table' => 'dotpay_instructions',
        'primary' => 'instruction_id',
        'fields' => array(
            'order_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'number' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'hash' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'bank_account' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'is_cash' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true),
            'currency' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'channel' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
        )
    );
    
    /**
     * Create table for this model
     * @return boolean
     */
    public static function create()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::$definition['table'].'` (
                `instruction_id` INT UNSIGNED NOT null AUTO_INCREMENT,
                `order_id` INT UNSIGNED NOT null,
                `number` varchar(64) NOT null,
                `hash` varchar(128) NOT null,
                `bank_account` VARCHAR(64),
                `is_cash` int(1) NOT null,
                `amount` decimal(10,2) NOT null,
                `currency` varchar(3) NOT null,
                `channel` INT UNSIGNED NOT null,
                PRIMARY KEY (`instruction_id`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }
    
    /**
     * Returns DotpayInstruction object with instruction which applies given order id
     * @param int $orderId Order id
     * @return \DotpayInstruction
     */
    public static function getByOrderId($orderId)
    {
        $result = Db::getInstance()->executeS(
            'SELECT instruction_id as id 
            FROM `'._DB_PREFIX_.self::$definition['table'].'` 
            WHERE order_id = '.(int)$orderId
        );
        if (!is_array($result) || count($result)<1) {
            return null;
        }
        return new DotpayInstruction($result[count($result)-1]['id']);
    }
    
    /**
     * Returns hash which is used to generate Dotpay payment page
     * @param array $payment Details of payment
     * @return string
     */
    public static function gethashFromPayment($payment)
    {
        $parts = explode('/', $payment['instruction']['instruction_url']);
        return $parts[count($parts)-2];
    }
    
    /**
     * Returns a page of bank, where customer can make his transfer
     * @param string $baseUrl Base url of Dotpay
     * @return string
     */
    public function getBankPage($baseUrl)
    {
        $url = $this->buildInstructionUrl($baseUrl);
        $html = file_get_html($url);
        if ($html==false) {
            return null;
        }
        return $html->getElementById('channel_container_')->firstChild()->getAttribute('href');
    }
    
    /**
     * Returns url with original pdf payment instruction on dotpay site
     * @param string $baseUrl Base url of Dotpay
     * @return string
     */
    public function getPdfUrl($baseUrl)
    {
        return $baseUrl.'instruction/pdf/'.$this->number.'/'.$this->hash.'/';
    }
    
    /**
     * Returns url of site with original payment instruction on Dotpay
     * @param string $baseUrl Base url of Dotpay
     * @return string
     */
    protected function buildInstructionUrl($baseUrl)
    {
        return $baseUrl.'instruction/'.$this->number.'/'.$this->hash.'/';
    }
}