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
 * Model of credit card brand
 */
class DotpayCardBrand extends ObjectModel
{
    /**
     *
     * @var string Brand name
     */
    public $name;
    
    /**
     *
     * @var string Brand image
     */
    public $image;
    
    public static $definition = array(
        'table' => 'dotpay_card_brands',
        'primary' => 'name',
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
            'image' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'required' => true),
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
                `name` varchar(20) NOT null,
                `image` varchar(192) DEFAULT null,
                PRIMARY KEY (`name`),
                UNIQUE KEY `brand_img` (`image`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }
    
    /**
     * Drop table for this model
     * @return boolean
     */
    public static function drop()
    {
        return Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `'._DB_PREFIX_.self::$definition['table'].'`;'
        );
    }
    
    /**
     * Prepares an object, if it's in a database
     * @param string $name
     */
    public function __construct($name = null)
    {
        if ($name) {
            $brand = Db::getInstance()->ExecuteS(
               'SELECT *  
                FROM `'._DB_PREFIX_.self::$definition['table'].'` 
                WHERE name = \''.$name.'\''
            );
            if (count($brand)) {
                $this->name = $brand[0]['name'];
                $this->image = $brand[0]['image'];
            }
        }
    }
    
    /**
     * Saves values to the database
     * @param bool $null_values
     * @param bool $auto_date
     * @return bool
     */
    public function save($null_values = false, $auto_date = true)
    {
        return Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.self::$definition['table'].'`
                (name, image)
            VALUES
                (\''.$this->name.'\', \''.$this->image.'\')
            ON DUPLICATE KEY UPDATE
                name  = \''.$this->name.'\',
                image = \''.$this->image.'\''
        );
    }
}
