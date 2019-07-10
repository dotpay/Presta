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

/**
 * Controller for managing card saved by One Click
 */
class dotpayocmanageModuleFrontController extends DotpayController
{
    /**
     * Displays a page with saved credit cards
     */
    public function initContent()
    {
        $this->display_column_left = true;
        parent::initContent();

        $this->context->smarty->assign(array(
            'cards' => DotpayCreditCard::getAllCardsForCustomer($this->context->customer->id),
            'onRemoveMessage' => $this->module->l('Do you want to deregister a saved card'),
            'onDoneMessage' => $this->module->l('The card was deregistered'),
            'onFailureMessage' => $this->module->l('An error occurred while deregistering the card'),
            'removeUrl' => $this->context->link->getModuleLink($this->module->name, 'ocremove')
        ));
        
        $this->setTemplate("ocmanage.tpl");
    }
}
