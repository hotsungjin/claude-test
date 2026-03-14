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

use Bundle\Component\CurrencyExchangeRate\CurrencyExchangeRateAdmin;
use Bundle\Component\CurrencyExchangeRate\CurrencyExchangeRateConfig;
use Bundle\Component\CurrencyExchangeRate\CurrencyList;
use Exception;
use Framework\Debug\Exception\LayerNotReloadException;
use Request;

/**
 * Class ExchangeRatePsController
 * @package Bundle\GlobalController\Admin\Policy
 * @author  Seung-gak Kim <surlira@godo.co.kr>
 */
class ExchangeRatePsController extends \Controller\Admin\Controller
{
    public function index()
    {
        $postValue = Request::post()->toArray();

        switch ($postValue['mode']) {
            case 'insert':
                $configList = new CurrencyList();
                foreach ($postValue['currency'] as $currency) {
                    if ($postValue['type'][$currency] == 'manual' && (!is_numeric($postValue['manual'][$currency]) || $postValue['manual'][$currency] <= 0)) {
                        throw new LayerNotReloadException(__('수동 환율을 입력하셔야 합니다.'));
                    }
                    if (trim($postValue['adjustment'][$currency]) == '') {
                        throw new LayerNotReloadException(__('조정 환율을 입력하셔야 합니다.'));
                    }
                    if (!is_numeric($postValue['adjustment'][$currency]) && !is_double($postValue['adjustment'][$currency])) {
                        throw new LayerNotReloadException(__('조정 환율을 숫자로 입력하셔야 합니다.'));
                    }

                    $configList->{$currency} = new CurrencyExchangeRateConfig(
                        $currency,
                        $postValue['type'][$currency],
                        $postValue['adjustment'][$currency],
                        $postValue['manual'][$currency]
                    );
                }
                try {
                    (new CurrencyExchangeRateAdmin())->postConfig($configList);
                    $this->layer(__('저장 되었습니다.'), 'parent.location.replace("exchange_rate_config.php");');
                } catch (Exception $e) {
                    throw new LayerNotReloadException($e->getMessage());
                }
                break;
        }
        exit;
    }
}
