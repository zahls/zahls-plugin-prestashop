<?php

/**
 * The QrCodeScan request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.7.5
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\QrCodeScan as ResponseQrCodeScan;

/**
 * QrCodeScan request class
 *
 * @package Zahls\Models\Request
 */
class QrCodeScan extends Base
{
    /** mandatory */
    protected string $sessionId;

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getResponseModel(): ResponseQrCodeScan
    {
        return new ResponseQrCodeScan();
    }
}
