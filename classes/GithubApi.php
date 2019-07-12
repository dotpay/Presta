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

class DotpayGithubApi
{
    private static $githubUrl = 'https://api.github.com';
    
    public static function getLatestVersion()
    {
        $url = self::$githubUrl.'/repos/dotpay/PrestaShop-1.6/releases/latest';
        $curl = new DotpayCurl();
        $curl->addOption(CURLOPT_URL, $url);
        self::setCurlOption($curl);
        $response = Tools::jsonDecode($curl->exec());
        $version = null;
        $url = '';
        if ($response instanceof \stdClass && isset($response->tag_name)) {
            $version = str_replace('v', '', $response->tag_name);
            if (isset($response->html_url)) {
                $url = $response->assets[0]->browser_download_url;
            }
        }
        return array(
            'version' => $version,
            'url' => $url
        );
    }
    
    /**
     * Set option for cUrl and return cUrl resource
     * @param resource $curl
     */
    private static function setCurlOption($curl)
    {
        $headers = array(
            'Accept: application/vnd.github.v3+json',
            'User-Agent: DotpayPluginForPrestashop'
        );
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
             ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
             ->addOption(CURLOPT_RETURNTRANSFER, 1)
             ->addOption(CURLOPT_HTTPHEADER, $headers)
             ->addOption(CURLOPT_TIMEOUT, 1000)
             ->addOption(CURLOPT_CUSTOMREQUEST, "GET");
    }
}
