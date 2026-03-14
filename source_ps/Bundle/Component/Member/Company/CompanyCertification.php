<?php
/*
 * Copyright (C) 2025 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium
 * is strictly prohibited.
 */

namespace Bundle\Component\Member\Company;

use DTO\Member\CompanyCertificationInsertDTO;
use Repository\Member\CompanyCertificationRepository;
use Component\Policy\Policy;
use Framework\Log\Logger;
use Framework\ObjectStorage\Service\ImageUploadService;

/**
 * 사업자 > 등록증 관련 클래스
 */
class CompanyCertification
{
    const DEFAULT_COMPANY_CERTIFICATION_FILE_SIZE = '2'; // MB
    const CERTIFICATION_IMAGE_UPLOAD_PATH = '/member/company/certification/%s';
    const ALLOW_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml'
    ];

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Policy
     */
    private $policy;
    /**
     * @var ImageUploadService
     */
    private $imageUploadService;
    /**
     * @var CompanyCertificationRepository
     */
    private $companyCertificationRepository;

    public function __construct(
        Logger $logger,
        Policy $policy,
        ImageUploadService $imageUploadService,
        CompanyCertificationRepository $companyCertificationRepository
    )
    {
        $this->logger = $logger;
        $this->policy = $policy;
        $this->imageUploadService = $imageUploadService;
        $this->companyCertificationRepository = $companyCertificationRepository;
    }

    /**
     * 사업자 등록증 파일 크기 설정
     * @param int $size
     * @return bool
     */
    public function setCompanyCertificationFileSize(int $size): bool
    {
        $joinItem = $this->policy->getValue('member.joinitem');
        $joinItem['comCertification']['maxSize'] = $size;

        return $this->policy->setValue('member.joinitem', $joinItem);
    }

    /**
     * 사업자 등록증 파일 크기
     *
     * @return int
     */
    public function getCompanyCertificationFileSize(): int
    {
        $joinItem = $this->policy->getValue('member.joinitem');

        return $joinItem['comCertification']['maxSize'] ?? self::DEFAULT_COMPANY_CERTIFICATION_FILE_SIZE;
    }

    /**
     * 사업자 등록증 이미지 OBS 업로드
     *
     * @param array $certificationFiles
     * @param int $memNo
     * @throws \Exception|\Throwable
     */
    public function upload(array $certificationFiles, int $memNo)
    {
        $fileCount = count($certificationFiles['tmp_name']);
        if ($fileCount !== 1) {
            throw new \Exception('사업자 등록증 파일은 1개만 업로드 가능합니다.');
        }

        // 기존파일이 있으면 삭제부터 진행.
        $certificationInfo = $this->companyCertificationRepository->findCertificationInfoByMemNo($memNo);
        if (!empty($certificationInfo)) {
            $deleteResult = $this->imageUploadService->deleteImage($certificationInfo['imageFilePath']);
            if ($deleteResult === false) {
                throw new \Exception('기존 사업자 등록증 이미지 삭제에 실패했습니다.');
            }
            $this->companyCertificationRepository->deleteCertificationInfo($certificationInfo['sno']);
        }

        // 파일 업로드 최대 사이즈
        $maxFileSize = $this->getCompanyCertificationFileSize();
        $result = $this->uploadToObs($maxFileSize, $certificationFiles, $memNo);
        $this->logger->channel('adminLog')->info('OBS IMAGE UPLOAD RESULT : ', $result);

        // 업로드 결과 처리
        if ($result['result']) {
            $this->insertCertificationInfo($memNo, $result); // 성공시 insert
        }  else {
            throw new \Exception('사업자 등록증 이미지 업로드에 실패했습니다.');
        }
    }

    /**
     * 결과 정보 저장
     * @param int $memNo
     * @param array $result
     * @return void
     * @throws \Throwable
     */
    private function insertCertificationInfo(int $memNo, array $result)
    {
        // DB 저장
        try {
            $dto = new CompanyCertificationInsertDTO($memNo, [
                'filePath' => $result['saveFileNm'], // img full url
                'fileName' => $result['uploadFileNm'], // file name
            ]);
            $this->companyCertificationRepository->insertCertificationInfo($dto);
        } catch (\Throwable $e) {
            // DB 저장 실패 시 OBS에서 파일 삭제
            $this->imageUploadService->deleteImage($result['saveFileNm']);
            throw $e;
        }
    }

    /**
     * 사업자 등록증 정보 조회
     * @param int $memNo
     * @return array
     */
    public function getCertification(int $memNo): array
    {
        return $this->companyCertificationRepository->findCertificationInfoByMemNo($memNo);
    }

    /**
     * 사업자 등록증 다운로드
     * @param int $sno
     * @return null
     * @throws \Framework\Debug\Exception\AlertBackException
     */
    public function download(int $sno)
    {
        $certification = $this->companyCertificationRepository->findCertificationInfoBySno($sno);
        if (empty($certification)) {
            throw new \Exception('찾을 수 없는 사업자등록증입니다.');
        }

        $this->imageUploadService->download($certification['imageFileNm'], $certification['imageFilePath']);
    }

    /**
     * 사업자 등록증 삭제
     * @param int $sno
     * @return void
     */
    public function delete(int $sno)
    {
        // OBS에서 파일 삭제
        $this->logger->channel('adminLog')->info('DELETE CERTIFICATION SNO: ' . $sno);
        $certification = $this->companyCertificationRepository->findCertificationInfoBySno($sno);
        if (!empty($certification)) {
            $result = $this->imageUploadService->deleteImage($certification['imageFilePath']);

            if ($result) {
                // DB에서 삭제
                $this->companyCertificationRepository->deleteCertificationInfo($sno);
            }
        }
    }

    /**
     * 사업자등록증을 OBS에 업로드하는 함수
     * @param int $maxFileSize
     * @param array $certificationFiles
     * @param int $memNo
     * @return array
     * @throws \Exception
     */
    private function uploadToObs(int $maxFileSize, array $certificationFiles, int $memNo): array
    {
        // 이미지 업로드 서비스에 허용되는 MIME 타입 설정
        $this->imageUploadService->setAllowMimeTypes(self::ALLOW_MIME_TYPES);

        // 파일 업로드
        $tempName = $certificationFiles['tmp_name'][0];
        $name = $certificationFiles['name'][0];
        $fileSize = (int)$certificationFiles['size'][0];

        // file 사이즈 초과 시 에러코드
        $fileSizeToMB = $fileSize / 1024 / 1024;
        $errorCode = $fileSizeToMB > $maxFileSize ? UPLOAD_ERR_INI_SIZE : UPLOAD_ERR_OK;

        // OBS 이미지 업로드
        $uploadFile = [
            'tmp_name' => $tempName,
            'name' => $name,
            'error' => $errorCode,
        ];
        return $this->imageUploadService->uploadImage(
            $uploadFile,
            sprintf(self::CERTIFICATION_IMAGE_UPLOAD_PATH, $memNo),
            false,
            $maxFileSize
        );
    }
}
