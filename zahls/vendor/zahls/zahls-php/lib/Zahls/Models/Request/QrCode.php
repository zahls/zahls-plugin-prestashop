<?php

/**
 * The QrCode request model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.7.5
 */

namespace Zahls\Models\Request;

use Zahls\Models\Base;
use Zahls\Models\Response\QrCode as ResponseQrCode;

/**
 * QrCode request class
 *
 * @package Zahls\Models\Request
 */
class QrCode extends Base
{
    /** mandatory */
    protected string $webshopUrl;

    public function getWebshopUrl(): string
    {
        return $this->webshopUrl;
    }

    public function setWebshopUrl(string $webshopUrl): void
    {
        $this->webshopUrl = $webshopUrl;
    }

    public function getResponseModel(): ResponseQrCode
    {
        return new ResponseQrCode();
    }
}
