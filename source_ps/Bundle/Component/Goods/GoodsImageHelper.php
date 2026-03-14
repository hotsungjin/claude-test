<?php
/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall to newer
 * versions in the future.
 *
 * @copyright ⓒ 2023, NHN COMMERCE Corp.
 */

namespace Bundle\Component\Goods;

class GoodsImageHelper
{
    /**
     * 이미지 복사를 지원하는 저장소인지 확인
     *
     * @param string $storageType
     * @param bool $storageCopyFl
     * @return bool
     */
    public static function isStorageTypeSupportImageCopy(string $storageType, bool $storageCopyFl = false): bool {

        // aws s3는 오브젝트 스토리지로 디렉토리 복사 기능 미지원
        if (strpos($storageType, 'amazonaws.com') !== false) {
            return false;
        }

        if ($storageCopyFl && $storageType === 'url') {
            return false;
        }

        return true;
    }

    public static function sortAndRemoveGoodsImageList(array $goodsImageConfig)
    {
        // 'add'로 시작하는 키만 정렬
        uksort($goodsImageConfig, function ($imageKey, $otherImageKey) use ($goodsImageConfig) {
            $isAdd = preg_match('/^add/', $imageKey);
            $isOtherAdd = preg_match('/^add/', $otherImageKey);

            if ($isAdd && !$isOtherAdd) {
                return 1; // 'add'라는 글자가 있는 키가 먼저
            } elseif (!$isAdd && $isOtherAdd) {
                return -1; // 'add'라는 글자가 없는 키가 뒤로
            } elseif ($isAdd && $isOtherAdd) {
                // 둘 다 'add'로 시작하면 'sequenceNumber' 값으로 비교
                $sequenceNumber = $goodsImageConfig[$imageKey]['sequenceNumber'];
                $otherSequenceNumber = $goodsImageConfig[$otherImageKey]['sequenceNumber'];

                if ($sequenceNumber !== null && $otherSequenceNumber !== null) {
                    return strcmp($sequenceNumber, $otherSequenceNumber); // 오름차순 정렬
                } elseif ($sequenceNumber === null) {
                    return -1; // key가 없는 경우 뒤로
                } elseif ($otherSequenceNumber === null) {
                    return 1; // key가 없는 경우 뒤로
                }
            }

            return 0; // 둘 다 'test'로 시작하지 않으면 순서 유지
        });

        // key 제거
        array_walk($goodsImageConfig, function (&$subArray) {
            if (isset($subArray['sequenceNumber'])) {
                unset($subArray['sequenceNumber']);
            }
        });

        return $goodsImageConfig;
    }

    /**
     * 상품 이미지 사이즈 설정이 연동된 항목 조회
     *
     * @return array
     */
    public static function searchUsedGoodsImageCds()
    {
        // 테마 관리에 사용된 imageCd 목록
        $displayConfig = \App::load('\\Component\\Display\\DisplayConfigAdmin');
        $tmpImageCds = $displayConfig->getImageCdsFromThemeConfig();
        $themeImageCds = is_array($tmpImageCds) ? array_column($tmpImageCds, 'imageCd') : [];

        // 인기상품 노출 관리에 사용된 imageCd 목록
        $populate = \App::load('\\Component\\Goods\\Populate');
        $tmpImageCds = $populate->getImageCdsFromPopulateSettings();
        $populateImageCds = is_array($tmpImageCds) ? array_column($tmpImageCds, 'imageCd') : [];

        // 관련상품 노출 설정에 사용된 imageCd 목록
        $relationConfig = gd_policy('display.relation');
        $relationImageCds = array_merge((array) $relationConfig['imageCd'] ?? [], (array) $relationConfig['mobileImageCd'] ?? []);

        // 검색창 추천상품 노출 설정에 사용된 imageCd 목록
        $recommendConfig = gd_policy('goods.recom');
        $recommendImageCds = (array) $recommendConfig['imageCd'] ?? [];

        // imageCd 항목별 정리
        $data['list'] = array_values(array_unique(array_merge($themeImageCds, $populateImageCds, $relationImageCds, $recommendImageCds)));
        $data['theme'] = $themeImageCds;
        $data['populate'] = $populateImageCds;
        $data['relation'] = $relationImageCds;
        $data['recommend'] = $recommendImageCds;

        return $data;
    }
}
