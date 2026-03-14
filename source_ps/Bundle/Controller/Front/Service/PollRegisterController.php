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

namespace Bundle\Controller\Front\Service;

use Component\Promotion\Poll;
use Framework\Debug\Exception\AlertBackException;
use Request;

class PollRegisterController extends \Controller\Front\Controller
{
    public function index()
    {
        $getValue = Request::get()->toArray();

        try{
            $poll = new Poll();

            $data = $poll->getPollData($getValue['code']);
            $returnScript = $poll->pollViewCheck($data);
            if ($returnScript) {
                throw new \Exception($returnScript);
            }
            $htmlContent = '';
            if ($data['pollHtmlContentFl'] == 'Y') {
                $htmlContent = stripslashes($data['pollHtmlContent']);

                if ($data['pollHtmlContentSameFl'] == 'N' && $poll->device == 'mobile') {
                    $htmlContent = stripslashes($data['pollHtmlContentMobile']);
                }

                // 웹취약점 개선사항 설문조사 에디터 업로드 이미지 alt 추가
                if ($htmlContent) {
                    $tag = "title";
                    preg_match_all( '@'.$tag.'="([^"]+)"@' , $htmlContent, $match );
                    $titleArr = array_pop($match);

                    foreach ($titleArr as $title) {
                        $htmlContent = str_replace('title="'.$title.'"', 'title="'.$title.'" alt="'.$title.'"', $htmlContent);
                    }
                }
            }
            $item = json_decode($data['pollItem'], true);

            foreach ($item['itemAnswerType'] as $key => $val) {
                if ($val == 'obj') {
                    $itemAnswer = $item['itemAnswer'][$key];
                    $itemLastAnswer = array_pop($itemAnswer);
                    if ($itemLastAnswer == 'ETC') {
                        $maxKey = max(array_keys($item['itemAnswer'][$key]));
                        $item['itemLastAnswer'][$key] = true;
                        $item['itemAnswer'][$key][$maxKey] = __('기타');
                    }
                }
            }

            unset($itemAnswer);
            unset($itemLastAnswer);
        } catch (AlertBackException $e) {
            throw new AlertBackException($e->getMessage());
        } catch (\Exception $e) {
            $this->js($returnScript);
        }

        $this->setData('code', $data['pollCode']);
        $this->setData('title', $data['pollTitle']);
        $this->setData('shortLen', $poll->shortLen);
        $this->setData('descriptLen', $poll->descriptLen);
        $this->setData('data', $item);
        $this->setData('htmlContentFl', $data['pollHtmlContentFl']);
        $this->setData('htmlContent', $htmlContent);
    }


}
