<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */
namespace Bundle\DTO\Member;

/**
 * 사업자 등록증 DB 저장 DTO
 */
class CompanyCertificationInsertDTO extends \Origin\DTO\AbstractDTO
{
    private $memNo;
    private $imageFileNm;
    private $imageFilePath;
    private $regDt;

    /**
     * @param int $memNo
     * @param array $result
     */
    public function __construct(int $memNo, array $result)
    {
        $this->memNo = $memNo;
        $this->imageFileNm = $result['fileName'] ?? '';
        $this->imageFilePath = $result['filePath'] ?? '';
        $this->regDt = date('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    public function getMemNo(): int
    {
        return $this->memNo;
    }

    /**
     * @return string
     */
    public function getImageFileNm(): string
    {
        return $this->imageFileNm;
    }

    /**
     * @return string
     */
    public function getImageFilePath(): string
    {
        return $this->imageFilePath;
    }

    /**
     * @return string
     */
    public function getRegDt(): string
    {
        return $this->regDt;
    }
}
