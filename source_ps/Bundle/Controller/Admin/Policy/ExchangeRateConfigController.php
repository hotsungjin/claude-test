<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */
namespace Bundle\Controller\Admin\Policy;

use Bundle\Component\CurrencyExchangeRate\CurrencyExchangeRate;
use Bundle\Component\CurrencyExchangeRate\CurrencyExchangeRateAdmin;
use Framework\Debug\Exception\LayerException;

/**
 * Class ExchangeRateConfigController
 * @package Bundle\GlobalController\Admin\Policy
 * @author  Seung-gak Kim <surlira@godo.co.kr>
 */
class ExchangeRateConfigController extends \Controller\Admin\Controller
{
    /**
     * {@inheritdoc}
     *
     * @throws LayerException
     * @author Jong-tae Ahn <qnibus@godo.co.kr>
     */
    public function index()
    {
        // --- 메뉴 설정
        $this->callMenu('policy', 'multiMall', 'exchangeRateConfig');

        // --- 관리자 데이터
        try {
            $currency = new CurrencyExchangeRate();
            $currencyAdmin = new CurrencyExchangeRateAdmin();
            $getValue = \Request::get()->toArray();

            $this->setData('publicData', $currency->fetchPublicData(date('Ymd')));
            $this->setData('storeData', $currency->fetch());
            $this->setData('config', $currency->getConfigListFromDao());
            $this->setData('globalCurrencyData', $currencyAdmin->getGlobalCurrency());
            $this->setData('exchangeRateLog', $currencyAdmin->getExchangeRateLog($getValue['page'], $getValue['pageNum']));
            $this->setData('page', \App::load(\Component\Page\Page::class));
        } catch (\Exception $e) {
            throw new LayerException($e->getMessage());
        }
    }
}
