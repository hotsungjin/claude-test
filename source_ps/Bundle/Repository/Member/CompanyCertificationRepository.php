<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Repository\Member;

use DTO\Member\CompanyCertificationInsertDTO;
use Origin\Model\Member\CompanyCertification;

class CompanyCertificationRepository
{
    public function insertCertificationInfo(CompanyCertificationInsertDTO $dto)
    {
        CompanyCertification::query()
            ->insert([
                'memNo' => $dto->getMemNo(),
                'imageFileNm' => $dto->getImageFileNm(),
                'imageFilePath' => $dto->getImageFilePath(),
                'regDt' => $dto->getRegDt(),
            ]);
    }

    /**
     * 사업자 등록증 정보를 memNo로 조회
     *
     * @param int $memNo
     * @return array
     */
    public function findCertificationInfoByMemNo(int $memNo): array
    {
        $certification = CompanyCertification::query()
            ->where('memNo', $memNo)
            ->first();

        return $certification ? $certification->toArray() : [];
    }

    /**
     * 사업자 등록증 정보를 sno로 조회
     * @param int $sno
     * @return array
     */
    public function findCertificationInfoBySno(int $sno): array
    {
        $certification = CompanyCertification::query()
            ->where('sno', $sno)
            ->first();

        return $certification ? $certification->toArray() : [];
    }

    /**
     * 사업자 등록증 정보를 sno로 삭제
     * @param int $sno
     * @return void
     */
    public function deleteCertificationInfo(int $sno)
    {
        CompanyCertification::query()
            ->where('sno', $sno)
            ->delete();
    }
}
