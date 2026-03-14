<?php
/*
 * Copyright (C) 2026 NHN COMMERCE. - All Rights Reserved
 *
 * Unauthorized copying or redistribution of this file in source and binary forms via any medium is strictly prohibited.
 */

namespace Bundle\Component\Cli;

use Framework\Command\JobScheduler\AbstractJob;
use Plugins\CipherConvertor\ConvertorServiceFactory;

/**
 * CipherConvertor Rollback CLI 실행
 *
 * @run
 *      /usr/local/php/bin/php /www/[USER_HOME]/route.php job --name="CliCipherRollback"
 *
 * @package Bundle\Component\Cli
 */
class CliCipherRollback extends AbstractJob
{
    /**
     * Job 실행 전 처리 로직
     *
     * @see Framework\Command\JobScheduler\JobConfig
     * @param object $config JobConfig 객체
     */
    protected function setup($config)
    {
        $config
            ->setIgnoreUserAbort(true)
            ->setMaxExecuteTime(18000)  // 최대 5시간
            ->setMaxMemoryLimit('5G');
    }

    /**
     * Job 실행
     *
     * @return boolean 실행 성공 여부
     */
    protected function execute()
    {
        if (php_sapi_name() == 'cli') {
            $startTime = date('Y-m-d H:i:s');
            $this->logMessage('[Rollback] Start: ' . $startTime);

            $cipherConvertorService = ConvertorServiceFactory::createConvertorService();
            $cipherConvertorService->rollback();

            $endTime = date('Y-m-d H:i:s');
            $this->logMessage('[Rollback] End: ' . $endTime . ' (Start: ' . $startTime . ')');
        }

        return true;
    }

    /**
     * Job 완료 처리
     *
     * @param $isSuccess
     * @return mixed|void
     */
    protected function complete($isSuccess)
    {
        if ($isSuccess) {
            $this->logMessage('OK');
        }
    }

    /**
     * Shutdown 예외처리
     *
     * @param string $code    시스템 다운 코드
     * @param string $message 시스템 다운 메시지
     */
    protected function shutdown($code, $message)
    {
        switch ($code) {
            case self::SHUTDOWN_NOT_ENOUGH_MEMORY:
                break;
            case self::SHUTDOWN_CONNECTION_TIMEOUT:
                break;
            case self::SHUTDOWN_CONNECTION_ABORTED:
                break;
            case self::SHUTDOWN_CONNECTION_TIMEOUT_ABORTED:
                break;
            case self::SHUTDOWN_CONNECTION_UNKNOWN_ERROR:
                break;
            default:
                break;
        }
    }
}
