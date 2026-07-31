<?php

/**
 * The QrCode response model
 *
 * @author    zahls <info@zahls.ch>
 * @copyright zahls
 * @since     v1.7.5
 */

namespace Zahls\Models\Response;

/**
 * QrCode response class
 *
 * @package Zahls\Models\Response
 */
class QrCode extends \Zahls\Models\Request\QrCode
{
    protected string $qrCode;

    protected string $png;

    protected string $svg;

    public function setPng(string $png): void
    {
        $this->png = $png;
    }

    public function getPng(): string
    {
        return $this->png;
    }

    public function setSvg(string $svg): void
    {
        $this->svg = $svg;
    }

    public function getSvg(): string
    {
        return $this->svg;
    }

    public function getQrCode(): string
    {
        return $this->qrCode;
    }

    public function setQrCode(string $qrCode): void
    {
        $this->qrCode = $qrCode;
    }
}
